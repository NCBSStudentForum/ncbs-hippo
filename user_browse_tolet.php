<!--
<script src="http://maps.googleapis.com/maps/api/js?libraries=places" type="text/javascript">
</script>

<script type="text/javascript">
    function initialize() {
        var input = document.getElementById('apartments_address');
        var autocomplete = new google.maps.places.Autocomplete(input);
        google.maps.event.addListener(autocomplete, 'place_changed', function () {
            var place = autocomplete.getPlace();
            document.getElementById('city2').value = place.name;
            document.getElementById('cityLat').value = place.geometry.location.lat();
            document.getElementById('cityLng').value = place.geometry.location.lng();
            alert("This function is working!");
            alert(place.name);
            alert(place.address_components[0].long_name);

        });
    }
    google.maps.event.addDomListener(window, 'load', initialize); 
</script>
-->

<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once 'tohtml.php';
include_once 'mail.php';
include_once 'check_access_permissions.php';

mustHaveAnyOfTheseRoles( array( 'USER' ) );

echo userHTML( );
$user = $_SESSION[ 'user' ];


// Show all apartments.
echo ' <h2>All available TO-LET listing </h2> ';

$myApartments = getTableEntries( 'apartments', 'type,rent,advance', "status='AVAILABLE'" );

echo printInfo( "Here you can send yourself details of a listing. Click on 
    <button disabled>Email me</button> in front of an entry to send details to
    yourself. Images will not be send.
    " );

echo '<div style="font-size:small;">';
echo '<table border="0">';
foreach( $myApartments as $apt )
{
    echo '<tr>';
    echo ' <form method="post" action=""> ';
    echo '<td>' . arrayToTableHTML( $apt, 'info', ''
                    , 'status,last_modified_on'  ) . '</td>';
    echo ' <td>
            <button name="response" value="Email me" > Email me </button> </td> ';
    echo '<input type="hidden" name="id" value="' . $apt[ 'id' ] . '" />';
    echo ' </form> ';
    echo '</tr>';
}
    
echo '</table>';
echo '</div>';

if( 'Email me' == __get__( $_POST, 'response', '' ) )
{
    $apt = getTableEntry( 'apartments', 'id', $_POST );

    $msg = initUserMsg( $user );
    $msg .= "<p> Following is the request entry </p>";
    $msg .= arrayToTableHTML( $apt, 'info' );

    $to = getLoginEmail( $user );
    $subject = "Your requested apartment listing @ " . $apt[ 'address' ] ;
    sendPlainTextEmail( $msg, $subject, $to );
}

echo goBackToPageLink( "user.php", "Go back" );

?>
