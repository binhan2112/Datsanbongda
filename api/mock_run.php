<?php
// Mock php://input content
$mockData = json_encode([
    'message' => 'sân rẻ nhất',
    'lat' => 10.0452,
    'lng' => 105.7469
]);

// Execute ai_chat.php in a separate process but capture its output. We can pass mockData via standard input!
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin
   1 => array("pipe", "w"),  // stdout
   2 => array("pipe", "w")   // stderr
);

$process = proc_open('C:\xampp\php\php.exe ai_chat.php', $descriptorspec, $pipes, 'c:\xampp\htdocs\Datsanbongda\api');

if (is_resource($process)) {
    fwrite($pipes[0], $mockData);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    proc_close($process);

    echo "STDOUT:\n$stdout\n\n";
    echo "STDERR:\n$stderr\n";
} else {
    echo "Failed to start process";
}
?>
