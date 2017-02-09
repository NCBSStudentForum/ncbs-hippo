<?php 
include_once( "header.php" );
include_once( "methods.php" );
include_once( "tohtml.php" );
include_once( "database.php" );
include_once 'display_content.php';
include_once "./check_access_permissions.php";

mustHaveAnyOfTheseRoles( 
    array( 'USER', 'ADMIN', 'BOOKMYVENUE_ADMIN', 'AWS_ADMIN', 'JC_ADMIN' ) 
);

echo userHTML( );

if( isMobile( ) )
    echo alertUser( 
        "If you are on a mobile device, you may like another interface.
        <a href=\"quickbook.php\">TAKE ME THERE</a>"
        );

// There is a form on this page which will send us to this page again. Therefore 
// we need to keep $_POST variable to a sane state.
$venues = getVenues( );
$venueNames = implode( ","
    , array_map( function( $x ) { return $x['id']; }, $venues )
    );


// Get the holiday on particular day. Write it infront of date to notify user.
$holidays = array( );
foreach( getTableEntries( 'holidays', 'date' ) as $holiday )
    $holidays[ $holiday['date'] ] = $holiday['description'];

// Construct a array to keep track of values. Since we are iterating over this 
// page many times.
$defaults = array( 
    'selected_dates' => dbDate( strtotime( 'today' ) )
    , 'selected_venues' => $venueNames
    , 'start_time' => date( 'H:i', strtotime( 'now ' ) )
    , 'end_time' => date( 'H:i', strtotime( 'now' ) + 6 * 3600 )
    );

// Update these values by $_POST variable.
foreach( $_POST as $key => $val )
    if( array_key_exists( $key, $defaults ) )
    {
        // All entries in $defaults are CSV.
        if( is_array( $val ) )
            $val = implode( ",", $val );
        $defaults[ $key ] = $val;
    }

$selectedDates = explode( ",", $defaults['selected_dates'] );
$selectedVenues = explode( ",", $defaults[ 'selected_venues' ] );

// Use selected_venues and construct a select list. Check all venues selected 
// before.
print_r( $_POST );

// Name of the option in this select list is 'venue'
$venueSelect = venuesToHTMLSelect( $venues, true
    , "selected_venues", $selectedVenues 
    );

echo "<form method=\"post\" action=\"\">
    <table>
    <tr>
    <th>
        Step 1: Pick dates
        <p class=\"note_to_user\">
        You can select multiple dates by clicking on popup calendar</p>
    </th>
    <th>
        Step 2: Select Venues
        <p class=\"note_to_user\">You can select multiple venues by holding 
            down Ctrl or Shift key</p>
    </th>
    <th>
        Step 3: Press <button disabled>Filter</button> to filter out 
        non-selected venues
    </th>
    </tr>
    <tr>
    <td><input type=\"text\" class=\"multidatespicker\" name=\"selected_dates\" 
        value=\"" . $defaults[ 'selected_dates' ] . "\" ></td>
    <td> $venueSelect </td>
    <td>
    <button style=\"float:right\" name=\"response\" value=\"submit\">Filter</button> ";

   echo " </td> </tr> </table> </form> <br> ";


   echo alertUser( 
       "
       <button class=\"display_request\" style=\"width:20px;height:20px\"></button>Pending requests
       <button class=\"display_event\" style=\"width:20px;height:20px\"></button>Booked slots
       <button class=\"display_event_with_public_event\" style=\"width:20px;height:20px\"></button>There is a public event at this slot.
       "
   );


echo "<h3>Step 4: Press + button to create an event at this time slot</h3>";
// Now generate the range of dates.
foreach( $selectedDates as $date )
{
    $thisdate = humanReadableDate( strtotime( $date  ) );
    $thisday = nameOfTheDay( $thisdate );

    $holidayText = '';
    if( array_key_exists( $date, $holidays ) )
        $holidayText =  '<div style="float:right"> &#9786 ' . $holidays[ $date ] . '</div>';

    $html = "<h4 class=\"info\"> <font color=\"blue\">
        $thisday, $thisdate, $holidayText </font></h4>";

    // Now generate eventline for each venue.
    foreach( $selectedVenues as $venueid )
        $html .= eventLineHTML( $date, $venueid );

    echo $html;
}

?>


