<?php

session_name('sess');
session_start();

if(!isset($_SESSION['started'])) {
  $_SESSION['started'] = time();
}
