<?php
/*
 * Fork version. getGitVersion() appends the short commit hash when this is
 * checked out as a git repository (see include/functions.php).
 */
define("FORK_NAME", "YSF Retro Dashboard");
define("FORK_REPO", "https://github.com/n9lvc5/ysf-retro-dashboard");
define("UPSTREAM_VERSION", "DG9VH 20210331-2");
define("VERSION", "1.0.0 (" . getGitVersion() . ")");
?>
