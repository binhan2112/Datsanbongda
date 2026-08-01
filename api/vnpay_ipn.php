<?php
require_once '../config/db.php';
require_once '../config/payment.php';
require_once '../includes/booking_helper.php';

$inputData = array();
$returnData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

$vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}
$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

$isMock = (VNPAY_HASH_SECRET === 'YOUR_HASH_SECRET_HERE');
$isValid = $isMock ? true : ($secureHash == $vnp_SecureHash);

if ($isValid) {
    $vnp_TxnRef = $inputData['vnp_TxnRef'];
    $vnp_Amount = $inputData['vnp_Amount'] / 100;
    $vnp_ResponseCode = $inputData['vnp_ResponseCode'];
    $vnp_TransactionNo = $inputData['vnp_TransactionNo'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE vnpay_txn_ref = :txnRef LIMIT 1");
        $stmt->execute(['txnRef' => $vnp_TxnRef]);
        $payment = $stmt->fetch();

        if ($payment) {
            $bookingId = $payment['booking_id'];
            if ($payment['status'] == 'pending') {
                if ($vnp_ResponseCode == '00') {
                    // Update payments
                    $pdo->prepare("UPDATE payments SET status = 'success', vnpay_response_code = :code, vnpay_trans_no = :transNo, paid_at = NOW() WHERE id = :id")
                        ->execute(['code' => $vnp_ResponseCode, 'transNo' => $vnp_TransactionNo, 'id' => $payment['id']]);
                    // Update bookings
                    $pdo->prepare("UPDATE bookings SET payment_status = 'paid', status = 'confirmed' WHERE id = :id")
                        ->execute(['id' => $bookingId]);
                    // Award points
                    awardPoints($bookingId, $pdo);
                    
                    $returnData['RspCode'] = '00';
                    $returnData['Message'] = 'Confirm Success';
                } else {
                    $pdo->prepare("UPDATE payments SET status = 'failed', vnpay_response_code = :code WHERE id = :id")
                        ->execute(['code' => $vnp_ResponseCode, 'id' => $payment['id']]);
                    $returnData['RspCode'] = '00';
                    $returnData['Message'] = 'Confirm Success (Failed payment)';
                }
            } else {
                $returnData['RspCode'] = '02';
                $returnData['Message'] = 'Order already confirmed';
            }
        } else {
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
        }
    } catch (Exception $e) {
        $returnData['RspCode'] = '99';
        $returnData['Message'] = 'Unknown error';
    }
} else {
    $returnData['RspCode'] = '97';
    $returnData['Message'] = 'Invalid signature';
}
echo json_encode($returnData);
?>
