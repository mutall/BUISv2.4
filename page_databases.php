<?php
//The databases page shows all the databases on the connected server.
//
//The the class, page_databases, is accessed via a shared library that extends
//the core mutall functionality.
require_once 'buis.php';
//
//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Crreate an instance of a page of databases
$page_databases = new page_databases($qstring);
//
?>
<html>
    <head>
        <title>page of Databases</title>

        <!--The general appearance of a mutall page is conrolled by this css-->
        <link rel="stylesheet" type="text/css" href="main.css">

        <!-- Refer to the core mutall library-->
        <script src="mutall.js"></script>
        
        <!-- Extend the mutall library-->
        <script src="buis.js"></script>
        
        <!--Activate the php Class Object to the equivalent js version-->
        <script>
            //
            //Interfacing equivalent javascript and php classes
            //
            //Create a js version of page_database object. Differentiate betwee
            //page_databases the class and page_databases the variable. Otherwise
            //Javascript gets confused. Hence the $ prefix. WAIT A MINUTE! There
            //are evenets of this page that need to be qualified with the name
            //of the class. So, revert back -- unless you adopt a rule of 
            //fomulating qualifier expression by appending teh $ prefix
            var page_databases = new page_databases(<?php echo $page_databases;?>);
        </script>

    </head>
    <body onload="page_databases.initialize()">

        <!-- The header section -->
        <header>
        </header>

        <!-- Display the databases-->
        <article>
            <?php
            //
            //Display this page using the default fashion, i.e, layout and mode
            $page_databases->display_page();
            ?>            
        </article>

        <!-- The footer section -->
        <footer>
            <!--
            View the tables of the selected database-->
            <input type="button" value="View Tables" id="view_tables" onclick='page_databases.view_tables()'>

            <!-- This tag is needed for reporting mutall errors. On clicking
            clear the error--> 
            <p id='error' onclick='this.innerHTML=""'/>

        </footer>
    </body>

</html>
    