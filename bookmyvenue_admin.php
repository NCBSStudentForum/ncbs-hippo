<?php 

// This is admin interface for book my venue.
// We are here to manage the requests.
include_once "header.php";
include_once "methods.php";
include_once "database.php";
include_once "tohtml.php";
include_once "check_access_permissions.php";

mustHaveAllOfTheseRoles( array( 'BOOKMYVENUE_ADMIN' ) );

echo userHTML( );


echo '<h2> Calendar administration </h2>';

echo '<table class="show_info">
    <tr>
    <td>
    <strong>Make sure you are logged-in using correct google account </strong>
    </strong>
    </td>
        <td>
            <a href="bookmyvenue_admin_synchronize_events_with_google_calendar.php">
            Synchronize public calendar </a> 
        </td>
    </tr>
    </table>
    ';

echo '<h2> Pending requests </h2>';
$requests = getPendingRequestsGroupedByGID( ); 

if( count( $requests ) == 0 )
    echo printInfo( "Cool! No request is pending for review" );


$html = '<table class="show_request">';
foreach( $requests as $r )
{
    $html .= '<form action="bookmyvenue_admin_request_review.php" method="post">';
    $html .= '<tr><td>';
    // Hide some buttons to send information to next page.
    $html .= '<input type="hidden" name="gid" value="' . $r['gid'] . '" />';
    $html .= '<input type="hidden" name="rid" value="' . $r['rid'] . '" />';
    $html .= arrayToTableHTML( $r, 'events'
        , ' ',  array( 'status', 'modified_by', 'timestamp', 'url', 'external_id' )
    );
    $html .= '</td>';
    $html .= '<td style="background:white">
        <button name="response" value="Review" title="Review request"> ' . 
            $symbReview . '</button> </td>';
    $html .= '</tr>';
    $html .= '</form>';
}
$html .= '</table>';
echo $html;

?>

<h2> Edit Upcoming Events </h2>
<?php
$html = '';
$events = getEventsGrouped( $sortby = 'date' );

$html .= "<table>";
foreach( $events as $event )
{
    $gid = $event['gid'];
    $eid = $event['eid'];
    $html .= "<form method=\"post\" action=\"bookmyvenue_admin_edit.php\">";
    $html .= "<tr><td>";
    $html .= arrayToTableHTML( $event, 'events', ''
        , Array( 'eid', 'calendar_id' , 'calendar_event_id', 'external_id'
                , 'gid', 'status' ) 
    );
    $html .= "</td>";
    $html .= "<td> <button name=\"response\" value=\"edit\">Edit</button></td>";
    $html .= "<input name=\"gid\" type=\"hidden\" value=\"$gid\" />";
    $html .= "<input name=\"eid\" type=\"hidden\" value=\"$eid\" />";
    $html .= "</form></tr>";
}

$html .= "</table>";
echo $html;

echo goBackToPageLink( "user.php", "Go back" );

?>

