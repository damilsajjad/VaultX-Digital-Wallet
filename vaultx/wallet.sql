-- ============================================================
-- VaultX Digital Wallet — Full Database Schema
-- Covers: Procedures, Transactions, Rollback, Triggers,
--         Try-Catch, UDFs, Views, Auto-generated Account No.
-- ============================================================

CREATE DATABASE IF NOT EXISTS vaultx;
USE vaultx;

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    account_no    VARCHAR(15)  NOT NULL UNIQUE,
    balance       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sender_id       INT,
    receiver_id     INT,
    amount          DECIMAL(12,2) NOT NULL,
    status          ENUM('success','failed','rolled_back') DEFAULT 'success',
    note            VARCHAR(255),
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE transaction_log (
    log_id       INT AUTO_INCREMENT PRIMARY KEY,
    txn_id       INT,
    action       VARCHAR(100),
    performed_by VARCHAR(50),
    log_time     DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_activity (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    admin_user  VARCHAR(50),
    action      VARCHAR(255),
    target_user INT,
    amount      DECIMAL(12,2),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- ADMIN USER (hardcoded)
-- ============================================================

CREATE TABLE admins (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- password: admin123 (bcrypt hash)
INSERT INTO admins (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
-- USER DEFINED FUNCTIONS
-- ============================================================

DELIMITER //

-- UDF: Check if balance is sufficient
CREATE FUNCTION fn_has_sufficient_balance(user_id INT, amount DECIMAL(12,2))
RETURNS TINYINT(1)
DETERMINISTIC
BEGIN
    DECLARE current_bal DECIMAL(12,2);
    SELECT balance INTO current_bal FROM users WHERE id = user_id;
    IF current_bal >= amount THEN
        RETURN 1;
    ELSE
        RETURN 0;
    END IF;
END //

-- UDF: Check if transaction limit is within allowed range (max 10000)
CREATE FUNCTION fn_within_limit(amount DECIMAL(12,2))
RETURNS TINYINT(1)
DETERMINISTIC
BEGIN
    IF amount > 0 AND amount <= 10000.00 THEN
        RETURN 1;
    ELSE
        RETURN 0;
    END IF;
END //

-- UDF: Generate account number
CREATE FUNCTION fn_generate_account_no()
RETURNS VARCHAR(15)
NOT DETERMINISTIC
BEGIN
    DECLARE new_acc VARCHAR(15);
    DECLARE exists_count INT;
    DECLARE rand_num INT;
    SET exists_count = 1;
    WHILE exists_count > 0 DO
        SET rand_num = FLOOR(100000 + RAND() * 899999);
        SET new_acc = CONCAT('VLT-', rand_num);
        SELECT COUNT(*) INTO exists_count FROM users WHERE account_no = new_acc;
    END WHILE;
    RETURN new_acc;
END //

DELIMITER ;

-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER //

-- PROCEDURE: Send Money (with Transaction, Rollback, Try-Catch)
CREATE PROCEDURE sp_send_money(
    IN  p_sender_id   INT,
    IN  p_receiver_acc VARCHAR(15),
    IN  p_amount      DECIMAL(12,2),
    IN  p_note        VARCHAR(255),
    OUT p_status      VARCHAR(50),
    OUT p_message     VARCHAR(255),
    OUT p_txn_id      INT
)
BEGIN
    DECLARE v_receiver_id INT DEFAULT NULL;
    DECLARE v_sufficient  TINYINT(1);
    DECLARE v_in_limit    TINYINT(1);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status  = 'failed';
        SET p_message = 'An unexpected error occurred. Transaction rolled back.';
        SET p_txn_id  = NULL;
    END;

    -- Validate amount limit using UDF
    SET v_in_limit = fn_within_limit(p_amount);
    IF v_in_limit = 0 THEN
        SET p_status  = 'failed';
        SET p_message = 'Amount must be between 1 and 10,000 per transaction.';
        SET p_txn_id  = NULL;
        -- Log failed txn
        INSERT INTO transactions (sender_id, receiver_id, amount, status, note)
        VALUES (p_sender_id, NULL, p_amount, 'failed', p_note);
        SET p_txn_id = LAST_INSERT_ID();
        LEAVE sp_send_money;
    END IF;

    -- Validate receiver account
    SELECT id INTO v_receiver_id FROM users WHERE account_no = p_receiver_acc LIMIT 1;
    IF v_receiver_id IS NULL THEN
        SET p_status  = 'failed';
        SET p_message = 'Receiver account not found. Transaction rolled back.';
        INSERT INTO transactions (sender_id, receiver_id, amount, status, note)
        VALUES (p_sender_id, NULL, p_amount, 'failed', p_note);
        SET p_txn_id = LAST_INSERT_ID();
        LEAVE sp_send_money;
    END IF;

    -- Prevent self-transfer
    IF v_receiver_id = p_sender_id THEN
        SET p_status  = 'failed';
        SET p_message = 'You cannot send money to yourself.';
        INSERT INTO transactions (sender_id, receiver_id, amount, status, note)
        VALUES (p_sender_id, v_receiver_id, p_amount, 'failed', p_note);
        SET p_txn_id = LAST_INSERT_ID();
        LEAVE sp_send_money;
    END IF;

    -- Check balance using UDF
    SET v_sufficient = fn_has_sufficient_balance(p_sender_id, p_amount);
    IF v_sufficient = 0 THEN
        SET p_status  = 'failed';
        SET p_message = 'Insufficient balance. Transaction rolled back.';
        INSERT INTO transactions (sender_id, receiver_id, amount, status, note)
        VALUES (p_sender_id, v_receiver_id, p_amount, 'failed', p_note);
        SET p_txn_id = LAST_INSERT_ID();
        LEAVE sp_send_money;
    END IF;

    -- Execute transfer
    START TRANSACTION;
        UPDATE users SET balance = balance - p_amount WHERE id = p_sender_id;
        UPDATE users SET balance = balance + p_amount WHERE id = v_receiver_id;
        INSERT INTO transactions (sender_id, receiver_id, amount, status, note)
        VALUES (p_sender_id, v_receiver_id, p_amount, 'success', p_note);
        SET p_txn_id = LAST_INSERT_ID();
    COMMIT;

    SET p_status  = 'success';
    SET p_message = 'Transaction completed successfully.';
END //


-- PROCEDURE: Admin Top-Up Balance
CREATE PROCEDURE sp_topup_balance(
    IN  p_account_no  VARCHAR(15),
    IN  p_amount      DECIMAL(12,2),
    IN  p_admin_user  VARCHAR(50),
    OUT p_status      VARCHAR(50),
    OUT p_message     VARCHAR(255)
)
BEGIN
    DECLARE v_user_id INT DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status  = 'failed';
        SET p_message = 'Top-up failed. Transaction rolled back.';
    END;

    SELECT id INTO v_user_id FROM users WHERE account_no = p_account_no LIMIT 1;
    IF v_user_id IS NULL THEN
        SET p_status  = 'failed';
        SET p_message = 'User account not found.';
        LEAVE sp_topup_balance;
    END IF;

    START TRANSACTION;
        UPDATE users SET balance = balance + p_amount WHERE id = v_user_id;
        INSERT INTO admin_activity (admin_user, action, target_user, amount)
        VALUES (p_admin_user, 'TOP_UP', v_user_id, p_amount);
    COMMIT;

    SET p_status  = 'success';
    SET p_message = CONCAT('Balance updated successfully for account ', p_account_no);
END //


-- PROCEDURE: Admin Rollback Last Transaction
CREATE PROCEDURE sp_rollback_last_transaction(
    IN  p_user_id    INT,
    IN  p_admin_user VARCHAR(50),
    OUT p_status     VARCHAR(50),
    OUT p_message    VARCHAR(255)
)
BEGIN
    DECLARE v_txn_id     INT DEFAULT NULL;
    DECLARE v_sender_id  INT;
    DECLARE v_receiver_id INT;
    DECLARE v_amount     DECIMAL(12,2);
    DECLARE v_txn_status VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status  = 'failed';
        SET p_message = 'Rollback failed due to an error.';
    END;

    -- Get last successful transaction involving this user as sender
    SELECT id, sender_id, receiver_id, amount, status
    INTO v_txn_id, v_sender_id, v_receiver_id, v_amount, v_txn_status
    FROM transactions
    WHERE sender_id = p_user_id AND status = 'success'
    ORDER BY created_at DESC
    LIMIT 1;

    IF v_txn_id IS NULL THEN
        SET p_status  = 'failed';
        SET p_message = 'No successful transaction found to roll back.';
        LEAVE sp_rollback_last_transaction;
    END IF;

    START TRANSACTION;
        UPDATE users SET balance = balance + v_amount WHERE id = v_sender_id;
        UPDATE users SET balance = balance - v_amount WHERE id = v_receiver_id;
        UPDATE transactions SET status = 'rolled_back' WHERE id = v_txn_id;
        INSERT INTO transaction_log (txn_id, action, performed_by)
        VALUES (v_txn_id, 'ROLLED_BACK_BY_ADMIN', p_admin_user);
        INSERT INTO admin_activity (admin_user, action, target_user, amount)
        VALUES (p_admin_user, 'ROLLBACK', p_user_id, v_amount);
    COMMIT;

    SET p_status  = 'success';
    SET p_message = CONCAT('Transaction #', v_txn_id, ' has been rolled back successfully.');
END //

DELIMITER ;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER //

-- Trigger: Auto-log every successful transaction
CREATE TRIGGER trg_after_txn_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    IF NEW.status = 'success' THEN
        INSERT INTO transaction_log (txn_id, action, performed_by)
        VALUES (NEW.id, 'TRANSACTION_CREATED', 'SYSTEM');
    END IF;
END //

-- Trigger: Prevent negative balance (safety net)
CREATE TRIGGER trg_before_balance_update
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.balance < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Balance cannot go negative.';
    END IF;
END //

DELIMITER ;

-- ============================================================
-- VIEWS
-- ============================================================

CREATE VIEW vw_transaction_history AS
SELECT
    t.id,
    t.amount,
    t.status,
    t.note,
    t.created_at,
    s.full_name   AS sender_name,
    s.account_no  AS sender_account,
    r.full_name   AS receiver_name,
    r.account_no  AS receiver_account
FROM transactions t
LEFT JOIN users s ON t.sender_id   = s.id
LEFT JOIN users r ON t.receiver_id = r.id;

CREATE VIEW vw_user_balances AS
SELECT id, full_name, email, account_no, balance, created_at
FROM users;
