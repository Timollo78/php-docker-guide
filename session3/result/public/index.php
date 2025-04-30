<?php

# Step 1
//echo $_SERVER['REQUEST_URI'];

# Step 2
//if ($_SERVER['REQUEST_URI'] === "/") {
//    echo "Welcome";
//} elseif ($_SERVER['REQUEST_URI'] === "/benchmark") {
//    header("Location: /benchmark.php");
//} else {
//    die ('Error 404');
//}

# Step 3
//switch ($_SERVER['REQUEST_URI']) {
//    case '/':
//        echo "Welcome";
//        break;
//    case '/benchmark':
//        header("Location: /benchmark.php");
//        break;
//    case '/hostinfo':
//        header("Location: /hostinfo.php");
//        break;
//    case '/phpinfo':
//        header("Location: /phpinfo.php");
//        break;
//    default:
//        die ('Error 404');
//}

# Step 4: 2 problems: 1, url gets automatically changed, 2 pages are not protected, still in the public folder. -> move scripts to src directory to a protected area: how can i access the pages now?

// Start with benchmark, dann alles nach und nach umbauen -> im tutorial vielleicht hier aufhören und auf ein weiteres tutorial verweisen?
switch ($_SERVER['REQUEST_URI']) {
    case '/':
        require_once "../src/home.php";
        home();
        break;
    case '/benchmark':
        require_once "../src/benchmark.php";
        benchmark();
        break;
    case '/hostinfo':
        require_once "../src/hostinfo.php";
        hostinfo();
        break;
    case '/phpinfo':
        require_once "../src/phpinfo.php";
        php_info();
        break;
    default:
        die ('Error 404');
}
