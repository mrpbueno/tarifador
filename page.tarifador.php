<?php

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 'call';
echo FreePBX::create()->Tarifador->showPage($page);