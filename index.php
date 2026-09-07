<?php
/**
 * CampusEvent Hub — Root Gateway Redirector
 */

// Route root directory requests directly to public/
header('Location: public/index.php');
exit;
