<?php
// Legacy/compat redirect: some clients may still request secretaryDashboard.php
// Redirect them to the new secretary.php page
header('Location: secretary.php', true, 302);
exit;
