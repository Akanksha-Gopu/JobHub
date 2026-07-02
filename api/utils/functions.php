<?php
// Global Helper Functions

/**
 * Send a JSON response to the client and terminate execution.
 *
 * @param string $status  "success" or "error"
 * @param string $message User friendly or system status message
 * @param mixed  $data    Payload containing data (optional)
 */
function sendResponse($status, $message = 'Success', $data = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit();
}
