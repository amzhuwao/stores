START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_dept_store;
CREATE TEMPORARY TABLE tmp_dept_store AS
SELECT d.dept_id,
       MIN(s.store_id) AS store_id
FROM departments d
JOIN stores s ON s.status='active'
 AND (
   s.store_code = CONCAT(d.dept_code,'001')
   OR s.store_code LIKE CONCAT(d.dept_code,'%')
   OR s.store_name LIKE CONCAT(d.dept_name,'%')
 )
GROUP BY d.dept_id;

DROP TEMPORARY TABLE IF EXISTS tmp_missing_receipts;
CREATE TEMPORARY TABLE tmp_missing_receipts AS
SELECT si.issue_id,
       sii.product_id,
       ds.store_id AS dest_store_id,
       sii.quantity_issued AS qty,
       COALESCE(sii.unit_price,0) AS unit_price,
       si.issued_by,
       si.issue_date,
       CASE WHEN si.requisition_id IS NULL THEN 'DIRECT_ISSUE_RECEIPT' ELSE 'ISSUE_RECEIPT' END AS reference_type
FROM stock_issues si
JOIN stock_issue_items sii ON sii.issue_id = si.issue_id
JOIN tmp_dept_store ds ON ds.dept_id = si.department_id
LEFT JOIN stock_transactions t
  ON t.product_id = sii.product_id
 AND t.store_id = ds.store_id
 AND t.reference_id = si.issue_id
 AND t.reference_type IN ('ISSUE_RECEIPT','DIRECT_ISSUE_RECEIPT')
WHERE ds.store_id <> si.store_id
  AND t.transaction_id IS NULL;

UPDATE stock s
JOIN (
  SELECT product_id, dest_store_id, SUM(qty) AS total_qty
  FROM tmp_missing_receipts
  GROUP BY product_id, dest_store_id
) m
  ON m.product_id = s.product_id
 AND m.dest_store_id = s.store_id
SET s.quantity_on_hand = s.quantity_on_hand + m.total_qty,
    s.updated_at = NOW();

INSERT INTO stock (product_id, store_id, quantity_on_hand, reorder_level)
SELECT m.product_id,
       m.dest_store_id,
       m.total_qty,
       COALESCE(p.reorder_level,0)
FROM (
  SELECT product_id, dest_store_id, SUM(qty) AS total_qty
  FROM tmp_missing_receipts
  GROUP BY product_id, dest_store_id
) m
JOIN products p ON p.product_id = m.product_id
LEFT JOIN stock s ON s.product_id = m.product_id AND s.store_id = m.dest_store_id
WHERE s.stock_id IS NULL;

INSERT INTO stock_transactions (
  product_id, store_id, transaction_type, reference_type, reference_id,
  quantity_change, unit_price, total_value, performed_by, transaction_date
)
SELECT r.product_id,
       r.dest_store_id,
       'receipt',
       r.reference_type,
       r.issue_id,
       r.qty,
       r.unit_price,
       (r.qty * r.unit_price),
       r.issued_by,
       r.issue_date
FROM tmp_missing_receipts r;

SELECT COUNT(*) AS backfilled_lines FROM tmp_missing_receipts;
SELECT p.product_name, st.store_name, s.quantity_on_hand
FROM stock s
JOIN products p ON p.product_id=s.product_id
JOIN stores st ON st.store_id=s.store_id
WHERE p.product_name LIKE '%Cappuccino%'
ORDER BY s.store_id;

COMMIT;
