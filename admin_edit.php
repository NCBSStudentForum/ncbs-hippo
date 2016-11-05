<?php

include_once ("header.php" );
include_once( "database.php" );
include_once( "tohtml.php" );

$gid = $_POST['gid'];
$eid = $_POST['eid'];

if( strcasecmp($_POST['response'], 'edit' ) == 0 )
{
    // Get a representative event of this group.
    $event = getEventsById( $gid, $eid );
    echo printInfo( "Chaging event $gid . $eid " );
    echo '<form method="post" action="admin_edit_submit.php">';
    echo dbTableToHTMLTable( 'events'
        , $defaults = $event
        , $editables = Array( 'status', 'class', 'is_public_event'
        , 'short_description', 'description'
    ));
    echo "</form>";
    echo goBackToPageLink( "admin.php", "Go back" );
}

?>