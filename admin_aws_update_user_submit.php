<?php 

include_once 'header.php' ;
include_once 'check_access_permissions.php' ;
include_once 'tohtml.php' ;
include_once 'database.php' ;
include_once 'methods.php';


echo userHTML( );

mustHaveAnyOfTheseRoles( Array( 'AWS_ADMIN' ) );

$toUpdate = array( 'title', 'joined_on', 'eligible_for_aws', 'status' );
$res = updateTable( 'logins', 'login', $toUpdate, $_POST ); 
if( $res )
{
    echo printInfo( "Successfully updated : " . implode(',', $toUpdate)  );

    // Rerun the scheduling script every time a change is made.
    rescheduleAWS( );
    goToPage( 'admin_aws.php', 1 );
    exit;
}

echo goBackToPageLink( 'admin.php', 'Go back' );

?>
