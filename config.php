<?php
$dbname = 'satiahostiranxyz_VentoVpn';
$usernamedb = 'satiahostiranxyz_VentoVpn';
$passworddb = 'AfYFbcB9Hvs639xJ5gyw';
$connect = mysqli_connect("localhost", $usernamedb, $passworddb, $dbname);
if ($connect->connect_error) { die("error" . $connect->connect_error); }
mysqli_set_charset($connect, "utf8mb4");
$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, ];
$dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8mb4";
try { $pdo = new PDO($dsn, $usernamedb, $passworddb, $options); } catch (\PDOException $e) { error_log("Database connection failed: " . $e->getMessage()); }
$APIKEY = '8003606306:AAG8zfiG0xL8v5FNJodj_c66xY78qtrcJrk';
$adminnumber = '1237083181';
$domainhosts = 'bot.psnco.sbs/VentoVpn'; 
$usernamebot = 'ConfigApiBot';
$groupID = '-1003854662958';
$topicID = 3;
$topicID_panel= 9;

?>
