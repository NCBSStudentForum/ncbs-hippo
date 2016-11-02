<?php

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once( "tohtml.php" );

echo userHTML( );

$requests = getRequestOfUsers( $_SESSION['user'], $status = 'PENDING' );

foreach( $requests as $request )
{
    echo "<form method=\"post\" action=\"user_manage.php\">";
    echo requestToHTML( $request );
    echo "</form>";
}


echo '<br>';
echo '<div style="float:left">';
echo goBackToPageLink( "user.php", "Go back" );
echo '</div>';

?>