-- Migration: Unify payment_slips to use transaction_id
-- This migration updates payment_slips to use transaction_id instead of order_id
-- Run this after ensuring transactions table is the primary orders table

-- 1. Add transaction_id column if not exists
ALTER TABLE payment_slips ADD COLUMN IF NOT EXISTS transaction_id INT DEFAULT NULL AFTER order_id;

-- 2. Add index for transaction_id
CREATE INDEX IF NOT EXISTS idx_transaction ON payment_slips(transaction_id);

-- 3. Copy order_id to transaction_id for existing records (if order_id has data but transaction_id is null)
UPDATE payment_slips SET transaction_id = order_id WHERE transaction_id IS NULL AND order_id IS NOT NULL;

-- 4. Add user_id column if not exists
ALTER TABLE payment_slips ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL AFTER transaction_id;

-- 5. Update user_id from transactions table
UPDATE payment_slips ps
JOIN transactions t ON ps.transaction_id = t.id
SET ps.user_id = t.user_id
WHERE ps.user_id IS NULL;

-- Note: We keep order_id column for backward compatibility but transaction_id is now the primary reference
