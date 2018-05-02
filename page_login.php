<?php
//This page is called to collect user login credentials, i.e., username and
//password, that can be used to access multiple databases.
//
//The page_login class that models this page is found in the Buis library
require_once "buis.php";
//
//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Pages get their query strings from the $_GET global variable
$page_login= new page_login($qstring);
?>
<html>
    <head>
        <title>Login</title>
        <!--The general appearance of a mutall page is controlled by this css.
        Remove it to be able to control the login page-->
        <!--link rel="stylesheet" type="text/css" href="mutall.css"-->
        <link rel="stylesheet" type="text/css" href="main.css">

        <!-- Script for referencing the prototypes for objects needed for 
        interacting with this page -->
        <script src="mutall.js"></script>

        <!-- The resolve reference to the page_login class--> 
        <script src="buis.js"></script>

        <script>

            //The php/js interfacace is implemented by echoing  json string
            //from the mutall php record variable
           var page_login  = new page_login(<?php echo $page_login; ?>);
        </script>

    </head>
    <body onload="page_login.initialize()">
        <?php
        //
        //Display the lognin page
        $page_login->display_page();
        ?>
        <!-- Collect the data from the record and return it to the caller.
        Save it to a session window variable so that the caller can pick it 
        up from there
        (Note that return is a keyword)
        -->
        <input id='ok' type="button" value="Ok" onclick="page_login.ok()"/>
        <input id='cancel' type="button" value="Cancel" onclick="page_login.cancel()"/>
        <!-- 
        Tag for reporting any errors reported by e.g. save. All mutall 
        pages should support this. Perhaps it should be added automatically
        on mutall construction-->
        <p id='error'/>

    </body>

</html>
