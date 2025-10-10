<?php header('X-Robots-Tag: noindex, nofollow, noarchive', true);
session_start(); if(empty($_SESSION['ok'])){ header('Location: login.php'); exit; }
require_once __DIR__.'/../config.php';
$id = (int)($_GET['id'] ?? 0);
if($id){ db()->prepare("DELETE FROM articles WHERE id=:id")->execute([':id'=>$id]); }
header('Location: dashboard.php');