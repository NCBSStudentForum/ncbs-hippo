<?php

include_once( "header.php" );
include_once( "methods.php" );
include_once( 'ldap.php' );


class BMVPDO extends PDO 
{
    function __construct( $host = 'localhost'  )
    {
        $conf = parse_ini_file( '/etc/hippo/hipporc', $process_section = TRUE );
        $options = array ( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION );
        $host = $conf['mysql']['host'];
        $port = $conf['mysql']['port'];
        if( $port == -1 )
            $port = 3306;

        $user = $conf['mysql']['user'];
        $password = $conf['mysql']['password'];
        $dbname = $conf['mysql']['database'];
        
        try {
            parent::__construct( 'mysql:host=' . $host . ";dbname=$dbname"
                , $user, $password, $options 
            );
        } catch( PDOException $e) {
            echo printWarning( "failed to connect to database: ".  $e->getMessage());
            $this->error = $e->getMessage( );
            echo goBackToPageLink( 'index.php', 0 );
            exit;
        }

    }
}

// Construct the PDO
$db = new BMVPDO( "localhost" );
initialize( );

/**
    * @brief Create all tables.
    *
    * @return 
 */
function initialize( )
{
    global $db;
    $res = $db->query( 
        'CREATE TABLE IF NOT EXISTS holidays 
            (date DATE NOT NULL PRIMARY KEY, description VARCHAR(100) NOT NULL)
        ' );
    $res = $db->query( 
        'CREATE TABLE IF NOT EXISTS visitors 
            ( title ENUM( "Mr.", "Ms.", "Dr.", "Prof" )
            , email VARCHAR(100) PRIMARY KEY
            , first_name VARCHAR(100) NOT NULL
            , middle_name VARCHAR(100)
            , last_name VARCHAR(100)
            , department VARCHAR(500)
            , institute VARCHAR(1000) NOT NULL
            )' );

    $res = $db->query( 
        'CREATE TABLE IF NOT EXISTS talks 
        ( id INT NOT NULL AUTO_INCREMENT
        , speaker VARCHAR(100) NOT NULL
        , host VARCHAR(100) NOT NULL
        , title VARCHAR(1000) NOT NULL
        , description TEXT 
        , PRIMARY KEY (id)
        )' );

    return $res;
}

function getVenues( $sortby = 'total_events' )
{
    global $db;
    // Sort according to total_events hosted by venue
    $res = $db->query( "SELECT * FROM venues ORDER BY $sortby, id" );
    return fetchEntries( $res );
}


function getTableSchema( $tableName )
{
    global $db;
    $stmt = $db->prepare( "DESCRIBE $tableName" );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getVenuesGroupsByType(  )
{
    global $db;
    // Sort according to total_events hosted by venue
    $venues = getVenues( );
    $newVenues = Array( );
    foreach( $venues as $venue )
    {
        $vtype = $venue['type'];
        if( ! array_key_exists( $vtype, $newVenues ) )
            $newVenues[ $vtype ] = Array();
        array_push( $newVenues[$vtype], $venue );
    }
    return $newVenues;
}

// Return the row representing venue for given venue id.
function getVenueById( $venueid )
{
    global $db;
    $venueid = trim( $venueid );
    $stmt = $db->prepare( "SELECT * FROM venues WHERE id=:id" );
    $stmt->bindValue( ':id', $venueid );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}


// Get all requests which are pending for review.
function getPendingRequestsGroupedByGID( )
{
    return getRequestsGroupedByGID( 'PENDING' );
}

// Get all requests with given status.
function getRequestsGroupedByGID( $status  )
{
    global $db;
    $stmt = $db->prepare( 'SELECT * FROM bookmyvenue_requests WHERE status=:status GROUP BY gid' );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

// Get all events with given status.
function getEventsByGroupId( $gid, $status = NULL  )
{
    global $db;
    $query = "SELECT * FROM events WHERE gid=:gid";
    if( $status )
        $query .= " AND status=:status ";

    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':gid', $gid );
    if( $status )
        $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

//  Get a event of given gid and eid. There is only one such event.
function getEventsById( $gid, $eid )
{
    global $db;
    $stmt = $db->prepare( 'SELECT * FROM events WHERE gid=:gid AND eid=:eid' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':eid', $eid );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Get list of requests made by this users. These requests must be 
    * newer than the current date minus 2 days and time else they won't show up.
    *
    * @param $userid
    * @param $status
    *
    * @return 
 */
function getRequestOfUser( $userid, $status = 'PENDING' )
{
    global $db;
    $stmt = $db->prepare( 'SELECT * FROM bookmyvenue_requests WHERE user=:user 
        AND status=:status AND date >= NOW() - INTERVAL 2 DAY
        GROUP BY gid' );
    $stmt->bindValue( ':user', $userid );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getEventsOfUser( $userid, $from = '-1 days', $status = 'VALID' )
{
    global $db;
    $from = date( 'Y-m-d', strtotime( $from ));
    $stmt = $db->prepare( 'SELECT * FROM events WHERE user=:user 
        AND date >= :from
        AND status=:status
        GROUP BY gid' );
    $stmt->bindValue( ':user', $userid );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':from', $from );
    $stmt->execute( );
    return fetchEntries( $stmt );

}

// Fetch entries from sqlite responses
function fetchEntries( $res, $how = PDO::FETCH_ASSOC )
{
    $array = Array( );
    if( $res ) {
        while( $row = $res->fetch( $how ) )
            array_push( $array, $row );
    }
    return $array;
}

// Get the request when group id and request id is given.
function getRequestById( $gid, $rid )
{
    global $db;
    $stmt = $db->prepare( 'SELECT * FROM bookmyvenue_requests WHERE gid=:gid AND rid=:rid' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':rid', $rid );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

// Return a list of requested with same group id.
function getRequestByGroupId( $gid )
{
    global $db;
    $stmt = $db->prepare( 'SELECT * FROM bookmyvenue_requests WHERE gid=:gid' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

// Return a list of requested with same group id and status
function getRequestByGroupIdAndStatus( $gid, $status )
{
    global $db;
    $stmt = $db->prepare( 'SELECT * FROM bookmyvenue_requests WHERE gid=:gid AND status=:status' );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Change the status of request.
    *
    * @param $requestId
    * @param $status
    *
    * @return true on success, false otherwise.
 */
function changeRequestStatus( $gid, $rid, $status )
{
    global $db;
    $stmt = $db->prepare( "UPDATE bookmyvenue_requests SET 
        status=:status WHERE gid=:gid AND rid=:rid"
    );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':rid', $rid );
    return $stmt->execute( );
}

/**
    * @brief Change status of all request identified by group id.
    *
    * @param $gid
    * @param $status
    *
    * @return 
 */
function changeStatusOfRequests( $gid, $status )
{
    global $db;
    $stmt = $db->prepare( "UPDATE bookmyvenue_requests SET status=:status WHERE gid=:gid" );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':gid', $gid );
    return $stmt->execute( );
}

function changeStatusOfEventGroup( $gid, $user, $status )
{
    global $db;
    $stmt = $db->prepare( "UPDATE events SET status=:status WHERE 
        gid=:gid AND user=:user" );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':user', $user );
    return $stmt->execute( );
}

/**
    * @brief Get the list of upcoming events.
 */
function getEvents( $from = 'today', $status = 'VALID' )
{
    global $db;
    $from = date( 'Y-m-d', strtotime( 'today' ));
    $stmt = $db->prepare( "SELECT * FROM events WHERE date >= :date AND 
        status=:status" );
    $stmt->bindValue( ':date', $from );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
  * @brief Get the list of upcoming events grouped by gid.
 */
function getEventsGrouped( $sortby = NULL, $from = 'today', $status = 'VALID' )
{
    global $db;
    if( ! $sortby )
        $sortby = '';
    else
        $sortby = " ORDER BY $sortby";

    $from = date( 'Y-m-d', strtotime( 'today' ));
    $stmt = $db->prepare( "SELECT * FROM events WHERE date >= :date AND 
        status=:status GROUP BY gid $sortby" );
    $stmt->bindValue( ':date', $from );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get the list of upcoming events.
 */
function getPublicEvents( $from = 'today', $status = 'VALID' )
{
    global $db;
    $from = date( 'Y-m-d', strtotime( 'today' ));
    $stmt = $db->prepare( "SELECT * FROM events WHERE date >= :date AND 
        status=:status AND is_public_event='YES'" );
    $stmt->bindValue( ':date', $from );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get list of public event on given day.
    *
    * @param $date
    * @param $status
    *
    * @return 
 */
function getPublicEventsOnThisDay( $date = 'today', $status = 'VALID' )
{
    global $db;
    $from = date( 'Y-m-d', strtotime( 'today' ));
    $stmt = $db->prepare( "SELECT * FROM events WHERE date = :date AND 
        status=:status AND is_public_event='YES'" );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getEventsOn( $day, $status = 'VALID')
{
    global $db;
    $stmt = $db->prepare( "SELECT * FROM events 
        WHERE status=:status AND date = :date" );
    $stmt->bindValue( ':date', $day );
    $stmt->bindValue( ':status', $status );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getEventsOnThisVenueOnThisday( $venue, $date, $status = 'VALID' )
{
    global $db;
    $stmt = $db->prepare( "SELECT * FROM events 
        WHERE venue=:venue AND status=:status AND date=:date" );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getEventsOnThisVenueBetweenTime( $venue, $date
    , $start_time, $end_time
   ,  $status = 'VALID' )
{
    global $db;
    $stmt = $db->prepare( 
        "SELECT * FROM events
        WHERE venue=:venue AND status=:status AND date=:date 
            AND ( start_time >= :start_time OR end_time <= :end_time )
        "
    );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':start_time', $start_time );
    $stmt->bindValue( ':end_time', $end_time );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getRequestsOnThisVenueOnThisday( $venue, $date, $status = 'PENDING' )
{
    global $db;
    $stmt = $db->prepare( "SELECT * FROM bookmyvenue_requests 
        WHERE venue=:venue AND status=:status AND date=:date" );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getRequestsOnThisVenueBetweenTime( $venue, $date
    , $start_time, $end_time
    , $status = 'PENDING' )
{
    global $db;
    $stmt = $db->prepare( 
        "SELECT * FROM bookmyvenue_requests 
        WHERE venue=:venue AND status=:status AND date=:date
            AND ( start_time >= :start_time OR end_time <= :end_time )
        " );
    $stmt->bindValue( ':date', $date );
    $stmt->bindValue( ':start_time', $start_time );
    $stmt->bindValue( ':end_time', $end_time );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}


/**
    * @brief Sunmit a request for review.
    *
    * @param $request
    *
    * @return  Group id of request.
 */
function submitRequest( $request )
{
    global $db;
    if( ! array_key_exists( 'user', $_SESSION ) )
    {
        echo printErrorSevere( "Error: I could not determine the name of user" );
        goToPage( "user.php", 5 );
    }

    if( ! array_key_exists( 'venue', $request ) )
    {
        echo printErrorSevere( "No venue found in your request" );
        goToPage( "user.php", 5 );
    }

    $repeatPat = $request[ 'repeat_pat' ];

    if( strlen( $repeatPat ) > 0 )
        $days = repeatPatToDays( $repeatPat );
    else 
        $days = Array( $request['date'] );

    if( count( $days ) < 1 )
    {
        echo minionEmbarrassed( "I could not generate list of slots for you reuqest" );
        return false;
    }

    $rid = 0;
    $results = Array( );
    $res = $db->query( 'SELECT MAX(gid) AS gid FROM bookmyvenue_requests' );
    $gid = intval($res->fetch( PDO::FETCH_ASSOC )['gid']) + 1;
    foreach( $days as $day ) 
    {
        $rid += 1;
        $query = $db->prepare( 
            "INSERT INTO bookmyvenue_requests ( 
                gid, rid, user, venue
                , title, description
                , date, start_time, end_time
                , status 
            ) VALUES ( 
                :gid, :rid, :user, :venue
                , :title, :description
                , :date , :start_time, :end_time
                , 'PENDING' 
            )");

        $query->bindValue( ':gid', $gid );
        $query->bindValue( ':rid', $rid );
        $query->bindValue( ':user', $_SESSION['user'] );
        $query->bindValue( ':venue' , $request['venue' ] );
        $query->bindValue( ':title', $request['title'] );
        $query->bindValue( ':description', $request['description'] );
        $query->bindValue( ':date', $day );
        $query->bindValue( ':start_time', $request['start_time'] );
        $query->bindValue( ':end_time', $request['end_time'] );
        $res = $query->execute();
        if( ! $res )
        {
            echo printWarning( "Could not submit request id $gid" );
            return 0;
        }
        array_push( $results, $res );
    }
    return $gid;
}


function increaseEventHostedByVenueByOne( $venueId )
{
    global $db;
    $stmt = $db->prepare( 'UPDATE venues SET total_events = total_events + 1 WHERE id=:id' );
    $stmt->bindValue( ':id', $venueId );
    $res = $stmt->execute( );
    return $res;
}

/**
    * @brief Create a new event in dateabase. The group id and event id of event 
    * is same as group id (gid) and rid of request which created it.
    *
    * @param $gid
    * @param $rid
    *
    * @return 
 */
function approveRequest( $gid, $rid )
{
    $request = getRequestById( $gid, $rid );

    global $db;
    $stmt = $db->prepare( 'INSERT INTO events (
        gid, eid, short_description, description, date, venue, start_time, end_time
        , user
    ) VALUES ( 
        :gid, :eid, :short_description, :description, :date, :venue, :start_time, :end_time 
        , :user
    )');
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':eid', $rid );
    $stmt->bindValue( ':short_description', $request['title'] );
    $stmt->bindValue( ':description', $request['description'] );
    $stmt->bindValue( ':date', $request['date'] );
    $stmt->bindValue( ':venue', $request['venue'] );
    $stmt->bindValue( ':start_time', $request['start_time'] );
    $stmt->bindValue( ':end_time', $request['end_time'] );
    $stmt->bindValue( ':user', $request['user'] );
    $res = $stmt->execute();
    if( $res )
    {
        changeRequestStatus( $gid, $rid, 'APPROVED' );
        // And update the count of number of events hosted by this venue.
        increaseEventHostedByVenueByOne( $request['venue'] );
    }

    return $res;
}

function rejectRequest( $gid, $rid )
{
    return changeRequestStatus( $gid, $rid, 'REJECTED' );
}


function actOnRequest( $gid, $rid, $status )
{
    if( $status == 'APPROVE' )
        approveRequest( $gid, $rid );
    elseif( $status == 'REJECT' )
        rejectRequest( $gid, $rid );
    else
        echo( printWarning( "unknown request " . $gid . '.' . $rid . 
        " or status " . $status ) );
}

function changeIfEventIsPublic( $gid, $eid, $status )
{
    global $db;
    $stmt = $db->prepare( "UPDATE events SET is_public_event=:status
        WHERE gid=:gid AND eid=:eid" );
    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':eid', $eid );
    return $stmt->execute();
}

// Fetch all events at given venue and given day-time.
function eventsAtThisVenue( $venue, $date, $time )
{
    $venue = trim( $venue );
    $date = trim( $date );
    $time = trim( $time );

    global $db;
    // Database reads in ISO format.
    $hDate = dbDate( $date );
    $clockT = date('H:i', $time );

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $db->prepare( 'SELECT * FROM events WHERE 
        date=:date AND venue=:venue AND start_time <= :time AND end_time > :time' );
    $stmt->bindValue( ':date', $hDate );
    $stmt->bindValue( ':time', $clockT );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

// Fetch all requests for given venue and given day-time.
function requestsForThisVenue( $venue, $date, $time )
{
    $venue = trim( $venue );
    $date = trim( $date );
    $time = trim( $time );

    global $db;
    // Database reads in ISO format.
    $hDate = dbDate( $date );
    $clockT = date('H:i', $time );
    //echo "Looking for request at $venue on $hDate at $clockT ";

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $db->prepare( 'SELECT * FROM bookmyvenue_requests WHERE 
        status=:status 
        AND date=:date AND venue=:venue
        AND start_time <= :time AND end_time > :time' 
    );
    $stmt->bindValue( ':status', 'PENDING' );
    $stmt->bindValue( ':date', $hDate );
    $stmt->bindValue( ':time', $clockT );
    $stmt->bindValue( ':venue', $venue );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get all public events at this time.
    *
    * @param $date
    * @param $time
    *
    * @return 
 */
function publicEvents( $date, $time )
{
    $date = trim( $date );
    $time = trim( $time );

    global $db;
    // Database reads in ISO format.
    $hDate = dbDate( $date );
    $clockT = date('H:i', $time );

    // NOTE: When people say 5pm to 7pm they usually don't want to keep 7pm slot
    // booked.
    $stmt = $db->prepare( 'SELECT * FROM events WHERE 
        date=:date AND start_time <= :time AND end_time > :time' );
    $stmt->bindValue( ':date', $hDate );
    $stmt->bindValue( ':time', $clockT );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Summary table for front page.
    *
    * @return 
 */
function summaryTable( )
{
    global $db;
    $allAWS = getAllAWS( );
    $nspeakers = count( getAWSSpeakers( ) );
    $nAws = count( $allAWS );
    $awsThisYear = count( getAWSFromPast( date( 'Y-01-01' ) ) );
    $html = '<table class="summary">';
    $html .= "
        <tr>
            <td>
            <a href=\"show_events.php\" target=\"_blank\">Event Calendar</a>
            </td>
        </tr>
        <tr>
            <td> <a href=\"user_aws_search.php\" target=\"_blank\">Search AWS</a> </td>
            <td> <a href=\"community_graphs.php\" target=\"_blank\" >See community graphs</a> </td>
            <td> <a href=\"aws_stats.php\" target=\"_blank\" >AWS Statistics </a> </td>
        </tr>
        <tr>
            <td>$nAws AWSs </td>
            <td> $nspeakers <a href=\"active_speakers.php\" target=\"_blank\" >active speakers</a></td>
            <td> $awsThisYear AWSs so far this year </td>
        </tr>";
    $html .= "</table>";
    return $html;
}

/**
    * @brief Update a group of requests. It can only modify fields which are set 
    * editable in function. 
    *
    * @param $gid
    * @param $options Any array as long as it contains fields with name in 
    * editables.
    *
    * @return  On success True, else False.
 */
function updateRequestGroup( $gid, $options )
{
    global $db;
    $editable = Array( "title", "description" );
    $fields = Array( );
    $placeholder = Array( );
    foreach( $options as $key => $val )
    {
        if( in_array( $key, $editable ) )
        {
            array_push( $fields, $key );
            array_push( $placeholder, "$key=:$key" );
        }
    }

    $placeholder = implode( ",", $placeholder );
    $query = "UPDATE bookmyvenue_requests SET $placeholder WHERE gid=:gid";

    $stmt = $db->prepare( $query );

    foreach( $fields as $f ) 
        $stmt->bindValue( ":$f", $options[ $f ] );

    $stmt->bindValue( ':gid', $gid );
    return $stmt->execute( );
}

function updateEventGroup( $gid, $options )
{
    global $db;
    $events = getEventsByGroupId( $gid );
    $results = Array( );
    foreach( $events as $event )
    {
        $res = updateEvent( $gid, $event['eid'], $options );
        if( ! $res )
            echo printWarning( "I could not update sub-event $eid" );
        array_push( $results, $res );
    }
    return (! in_array( FALSE, $results ));

}

function updateEvent( $gid, $eid, $options )
{
    global $db;
    $editable = Array( "short_description", "description"
        , "is_public_event", "status", "class" 
    );
    $fields = Array( );
    $placeholder = Array( );
    foreach( $options as $key => $val )
    {
        if( in_array( $key, $editable ) )
        {
            array_push( $fields, $key );
            array_push( $placeholder, "$key=:$key" );
        }
    }

    $placeholder = implode( ",", $placeholder );
    $query = "UPDATE events SET $placeholder WHERE gid=:gid AND eid=:eid";

    $stmt = $db->prepare( $query );

    foreach( $fields as $f ) 
        $stmt->bindValue( ":$f", $options[ $f ] );

    $stmt->bindValue( ':gid', $gid );
    $stmt->bindValue( ':eid', $eid );
    return $stmt->execute( );
}

// Create user if does not exists and fill information form LDAP server.
function createUserOrUpdateLogin( $userid, $ldapInfo = Array() )
{
    global $db;
    $stmt = $db->prepare( 
       "INSERT IGNORE INTO logins
        (id, login, first_name, last_name, email, created_on, institute, laboffice) 
            VALUES 
            (:id, :login, :fname, :lname, :email,  'NOW()', :institute, :laboffice)" 
        );

    $institute = NULL;
    if( count( $ldapInfo ) > 0 ) 
        $institute = 'NCBS Bangalore';

    //var_dump( $ldapInfo );

    $stmt->bindValue( ':login', $userid );
    $stmt->bindValue( ':id', __get__( $ldapInfo, "uid", NULL ));
    $stmt->bindValue( ':fname', __get__( $ldapInfo, "fname", NULL ));
    $stmt->bindValue( ':lname', __get__( $ldapInfo, "lname", NULL ));
    $stmt->bindValue( ':email', __get__( $ldapInfo, 'email', NULL ));
    $stmt->bindValue( ':laboffice', __get__( $ldapInfo, 'laboffice', NULL ));
    $stmt->bindValue( ':institute', $institute );
    $stmt->execute( );

    $stmt = $db->prepare( "UPDATE logins SET last_login=NOW() WHERE login=:login" );
    $stmt->bindValue( ':login', $userid );
    return $stmt->execute( );
}

/**
    * @brief Get all logins.
    *
    * @return 
 */
function getLogins( $status = ''  )
{
    global $db;
    $where = '';
    if( $status )
        $where = " WHERE status='$status' ";
    $query = "SELECT * FROM logins $where ORDER BY joined_on DESC";
    $stmt = $db->query( $query );
    $stmt->execute( );
    return  fetchEntries( $stmt );
}

function getLoginIds( )
{
    global $db;
    $stmt = $db->query( 'SELECT login FROM logins' );
    $stmt->execute( );
    $results =  fetchEntries( $stmt );
    $logins = Array();
    foreach( $results as $key => $val )
        array_push( $logins, $val['login'] );
    return $logins;
}

/**
    * @brief Get user info from database.
    *
    * @param $user Login id of user.
    *
    * @return Array.
 */
function getUserInfo( $user )
{
    global $db;
    $stmt = $db->prepare( "SELECT * FROM logins WHERE login=:login" );
    $stmt->bindValue( ":login", $user );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getLoginInfo( $login_name )
{
    return getUserInfo( $login_name );
}

function getRoles( $user )
{
    global $db;
    $stmt = $db->prepare( 'SELECT roles FROM logins WHERE login=:login' );
    $stmt->bindValue( ':login', $user );
    $stmt->execute( );
    $res = $stmt->fetch( PDO::FETCH_ASSOC );
    return explode( ",", $res['roles'] );
}

function getMyAws( $user )
{
    global $db;

    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker 
        ORDER BY date DESC "; 
    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':speaker', $user );
    $stmt->execute( );
    return fetchEntries( $stmt );
}


function getMyAwsOn( $user, $date )
{
    global $db;

    $query = "SELECT * FROM annual_work_seminars 
        WHERE speaker=:speaker AND date=:date ORDER BY date DESC "; 
    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':speaker', $user );
    $stmt->bindValue( ':date', $date );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getAwsById( $id )
{
    global $db;

    $query = "SELECT * FROM annual_work_seminars WHERE id=:id";
    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':id', $id );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Return only recent most AWS given by this speaker.
    *
    * @param $speaker
    *
    * @return 
 */
function getLastAwsOfSpeaker( $speaker )
{
    global $db;
    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker 
        ORDER BY date DESC LIMIT 1";
    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':speaker', $speaker );
    $stmt->execute( );
    # Only return the last one.
    return $stmt->fetch( PDO::FETCH_ASSOC );

}

/**
    * @brief Return all AWS given by this speaker.
    *
    * @param $speaker
    *
    * @return 
 */
function getAwsOfSpeaker( $speaker )
{
    global $db;
    $query = "SELECT * FROM annual_work_seminars WHERE speaker=:speaker 
        ORDER BY date DESC" ;
    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':speaker', $speaker );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getSupervisors( )
{
    global $db;
    $faculty = getFaculty( $status = 'ACTIVE' );
    $stmt = $db->query( 'SELECT * FROM supervisors ORDER BY first_name' );
    $stmt->execute( );
    $supervisors = fetchEntries( $stmt );
    foreach( $supervisors as $super )
        array_push( $faculty, $super );
    return $faculty;
}

/**
    * @brief 
    *
    * @param $tablename
    * @param $orderby
    *
    * @return 
 */
function getTableEntries( $tablename, $orderby = '' )
{
    global $db;
    $query = "SELECT * FROM $tablename ";

    if( $orderby )
        $query .= " ORDER BY $orderby";

    $stmt = $db->query( $query );
    return fetchEntries( $stmt );
}


/**
    * @brief Insert a new entry in table.
    *
    * @param $tablename
    * @param $keys, Keys to update/insert in table.
    * @param $data
    *
    * @return  The id of newly inserted entry on success. Null otherwise.
 */
function insertIntoTable( $tablename, $keys, $data )
{
    global $db;

    if( gettype( $keys ) == "string" )
        $keys = explode( ',', $keys );

    $values = Array( );
    $cols = Array( );
    foreach( $keys as $k )
    {
        // If values for this key in $data is null then don't use it here.
        if( $data[$k] )
        {
            array_push( $cols, "$k" );
            array_push( $values, ":$k" );
        }
            
    }

    $keysT = implode( ",", $cols );
    $values = implode( ",", $values );
    $query = "INSERT INTO $tablename ( $keysT ) VALUES ( $values )";

    $stmt = $db->prepare( $query );
    foreach( $cols as $k )
    {
        $value = $data[$k];
        if( gettype( $value ) == 'array' )
            $value = implode( ',', $value );
        $stmt->bindValue( ":$k", $value );
    }
    $res = $stmt->execute( );
    if( $res )
    {
        // When created return the id of table else return null;
        $stmt = $db->query( "SELECT LAST_INSERT_ID() FROM $tablename" );
        $stmt->execute( );
        return $stmt->fetch( PDO::FETCH_ASSOC );
    }
    return null;
}

/**
    * @brief Delete an entry from table. 
    *
    * @param $tableName
    * @param $keys
    * @param $data
    *
    * @return Status of execute statement.
 */
function deleteFromTable( $tablename, $keys, $data )
{
    global $db;

    if( gettype( $keys ) == "string" )
        $keys = explode( ',', $keys );

    $values = Array( );
    $cols = Array( );
    foreach( $keys as $k )
        if( $data[$k] )
        {
            array_push( $cols, "$k" );
            array_push( $values, ":$k" );
        }

    $keysT = implode( ",", $cols );
    $values = implode( ",", $values );
    $query = "DELETE FROM $tablename WHERE ";

    $whereClause = array( );
    foreach( $cols as $k )
        array_push( $whereClause, "$k=:$k" );

    $query .= implode( " AND ", $whereClause );


    $stmt = $db->prepare( $query );
    foreach( $cols as $k )
    {
        $value = $data[$k];
        if( gettype( $value ) == 'array' )
            $value = implode( ',', $value );
        $stmt->bindValue( ":$k", $value );
    }
    $res = $stmt->execute( );
    return $res;
}

/**
    * @brief A generic function to update a table.
    *
    * @param $tablename Name of table.
    * @param $wherekeys WHERE $wherekey=wherekeyval,... etc.
    * @param $keys Keys to be updated.
    * @param $data An array having all data.
    *
    * @return 
 */
function updateTable( $tablename, $wherekeys, $keys, $data )
{
    global $db;
    $query = "UPDATE $tablename SET ";

    if( gettype( $wherekeys ) == "string" ) // Only one key
        $wherekeys = explode( ",", $wherekeys );
    if( gettype( $keys ) == "string" )
        $keys = explode(",",  $keys );

    $whereclause = array( );
    foreach( $wherekeys as $wkey )
        array_push( $whereclause, "$wkey=:$wkey" );

    $whereclause = implode( " AND ", $whereclause );

    $values = Array( );
    $cols = Array();
    foreach( $keys as $k )
    {
        // If values for this key in $data is null then don't use it here.
        if( ! $data[$k] )
            $data[ $k ] = null;

        array_push( $cols, $k );
        array_push( $values, "$k=:$k" );
    }
    $values = implode( ",", $values );
    $query .= " $values WHERE $whereclause";

    $stmt = $db->prepare( $query );
    foreach( $cols as $k )
    {
        $value = $data[$k];
        if( gettype( $value ) == 'array' )
            $value = implode( ',', $value );

        $stmt->bindValue( ":$k", $value );
    }

    foreach( $wherekeys as $wherekey )
        $stmt->bindValue( ":$wherekey", $data[$wherekey] );

    return $stmt->execute( );
}


/**
    * @brief Get the AWS scheduled in future for this speaker. 
    *
    * @param $speaker The speaker.
    *
    * @return  Array.
 */
function  scheduledAWSInFuture( $speaker )
{
    global $db;
    $stmt = $db->prepare( 
        "SELECT * FROM upcoming_aws WHERE
        speaker=:speaker AND date > NOW() 
        " );
    $stmt->bindValue( ":speaker", $speaker );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Check if there is a temporary AWS schedule.
    *
    * @param $speaker
    *
    * @return 
 */
function temporaryAwsSchedule( $speaker )
{
    global $db;
    $stmt = $db->prepare( 
        "SELECT * FROM aws_temp_schedule WHERE
        speaker=:speaker AND date > NOW() 
        " );
    $stmt->bindValue( ":speaker", $speaker );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Fetch faculty from database. Order by last-name
    *
    * @param $status
    *
    * @return 
 */
function getFaculty( $status = '', $order_by = 'first_name' )
{
    global $db;
    $query = 'SELECT * FROM faculty ';
    if( $status )
        $query .= " WHERE status=:status ";

    $query .= " ORDER BY  '$order_by' ";

    $stmt = $db->prepare( $query );
    if( $status )
        $stmt->bindValue( ':status', $status );

    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get all pending requests for this user.
    *
    * @param $user Name of the user.
    * @param $status status of the request.
    *
    * @return 
 */
function getAwsRequestsByUser( $user, $status = 'PENDING' )
{
    global $db;
    $query = "SELECT * FROM aws_requests WHERE status=:status AND speaker=:speaker";
    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':status', $status );
    $stmt->bindValue( ':speaker', $user );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getAwsRequestById( $id )
{
    global $db;
    $query = "SELECT * FROM aws_requests WHERE id=:id";
    $stmt = $db->prepare( $query );
    $stmt->bindValue( ':id', $id );
    $stmt->execute( );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function getPendingAWSRequests( )
{
    global $db;
    $stmt = $db->query( "SELECT * FROM aws_requests WHERE status='PENDING'" );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getAllAWS( )
{
    global $db;
    $stmt = $db->query( "SELECT * FROM annual_work_seminars ORDER BY date DESC"  );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Return AWS from last n years.
    *
    * @param $years
    *
    * @return  Array of events.
 */
function getAWSFromPast( $from  )
{
    global $db;
    $stmt = $db->query( "SELECT * FROM annual_work_seminars 
        WHERE date >= '$from' ORDER BY date DESC, speaker
    " );
    $stmt->execute( );
    return fetchEntries( $stmt );
}


/**
    * @brief Get AWS users.
    *
    * @return Array containing AWS speakers.
 */
function getAWSSpeakers( $sortby = False )
{
    global $db;
    $sortExpr = '';
    if( $sortby )
        $sortExpr = " ORDER BY '$sortby'";

    $stmt = $db->query( 
        "SELECT * FROM logins WHERE status='ACTIVE' AND eligible_for_aws='YES' $sortExpr " 
    );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Return AWS entries schedules by my minion..
    *
    * @return 
 */
function getTentativeAWSSchedule( )
{
    global $db;
    $stmt = $db->query( "SELECT * FROM aws_temp_schedule ORDER BY date" );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Get all upcoming AWSes. Closest to today first (Ascending date).
    * 
    * @return Array of upcming AWS.
 */
function getUpcomingAWS( )
{
    global $db;
    $stmt = $db->query( 
        "SELECT * FROM upcoming_aws WHERE date >= CURDATE() ORDER BY date" 
        );
    $stmt->execute( );
    return fetchEntries( $stmt );
}

function getUpcomingAWSById( $id )
{
    global $db;
    $stmt = $db->query( "SELECT * FROM upcoming_aws WHERE id = $id " );
    $stmt->execute( );
    return  $stmt->fetch( PDO::FETCH_ASSOC );
}

/**
    * @brief Accept a auto generated schedule. We put the entry into table 
    * upcoming_aws and delete this entry from aws_temp_schedule tables. In case 
    * of any failure, leave everything untouched.
    *
    * @param $speaker
    * @param $date
    *
    * @return 
 */
function acceptScheduleOfAWS( $speaker, $date )
{
    global $db;
    $db->beginTransaction( );

    $stmt = $db->prepare( 
        'INSERT INTO upcoming_aws ( speaker, date ) VALUES ( :speaker, :date )' 
    );

    $stmt->bindValue( ':speaker', $speaker );
    $stmt->bindValue( ':date', $date );

    try {

        $res = $stmt->execute( );
        // delete this row from temp table.
        $stmt = $db->prepare( 'DELETE FROM aws_temp_schedule WHERE 
            speaker=:speaker AND date=:date
            ' );
        $stmt->bindValue( ':speaker', $speaker );
        $stmt->bindValue( ':date', $date );
        $res = $stmt->execute( );

        // If this happens, I must not commit the previous results into table.
        if( ! $res )
        {
            $db->rollBack( );
            return False;
        }
    } 
    catch (Exception $e) 
    {
        $db->rollBack( );
        echo minionEmbarrassed( 
            "Failed to insert $speaker, $date into database: " . $e->getMessage() 
        );
        return False;
    }

    $db->commit( );
    return True;
}

/**
    * @brief Query AWS database of given query.
    *
    * @param $query
    *
    * @return  List of AWS with matching query.
 */
function queryAWS( $query )
{
    if( strlen( $query ) == 0 )
        return array( );

    if( strlen( $query ) < 3 )
    {
        echo printWarning( "Query is too small" );
        return array( );
    }

    global $db;
    $stmt = $db->query( "SELECT * FROM annual_work_seminars 
        WHERE LOWER(abstract) LIKE LOWER('%$query%')" 
    ); 
    $stmt->execute( );
    return fetchEntries( $stmt );
}

/**
    * @brief Clear a given AWS from upcoming AWS list.
    *
    * @param $speaker
    * @param $date
    *
    * @return 
 */
function clearUpcomingAWS( $speaker, $date )
{
    global $db;
    $stmt = $db->prepare( 
        "DELETE FROM upcoming_aws WHERE speaker=:speaker AND date=:date" 
    );

    $stmt->bindValue( ':speaker', $speaker );
    $stmt->bindValue( ':date', $date );
    return $stmt->execute( );
}

/**
    * @brief Delete an entry from annual_work_seminars table.
    *
    * @param $speaker
    * @param $date
    *
    * @return True, on success. False otherwise.
 */
function deleteAWSEntry( $speaker, $date )
{
    global $db;
    $stmt = $db->prepare( 
        "DELETE FROM annual_work_seminars WHERE speaker=:speaker AND date=:date" 
    );
    $stmt->bindValue( ':speaker', $speaker );
    $stmt->bindValue( ':date', $date );
    return $stmt->execute( );
}

function getHolidays( $from = NULL )
{
    global $db;
    if( ! $from )
        $from = date( 'Y-m-d', strtotime( 'today' ) );
    $stmt = $db->query( "SELECT * FROM holidays WHERE date >= '$from' ORDER BY date" );
    return fetchEntries( $stmt );
}

// Deprecated: Images are stored in ./pictures/ folder.
// /**
//     * @brief Get user data from logins_metadata table. 
//     *
//     * @param $user
//     *
//     * @return 
//  */
// function getUserPicuture( $user ) 
// {
//     global $db;
//     $res = $db->query( "SELECT user_image FROM logins_metadata 
//         WHERE login='$user'" );
//     return $res->fetch( PDO::FETCH_ASSOC ); // } 
?>

