<!-- <script src="http://code.highcharts.com/highcharts.js"></script> -->
<script src="./node_modules/highcharts/highcharts.js"></script>
<?php

include_once 'header.php';
include_once 'database.php';
include_once 'methods.php';


$upto = dbDate( 'tomorrow' );
$requests = getTableEntries( 'bookmyvenue_requests', 'date'
                , "date >= '2017-02-28' AND date <= '$upto'" );
$nApproved = 0;
$nRejected = 0;
$nCancelled = 0;
$nPending = 0;
$nOther = 0;
$timeForAction = array( );

$firstDate = $requests[0]['date'];
$lastDate = end( $requests )['date'];
$timeInterval = strtotime( $lastDate ) - strtotime( $firstDate );

foreach( $requests as $r )
{
    if( $r[ 'status' ] == 'PENDING' )
        $nPending += 1;

    else if( $r[ 'status' ] == 'APPROVED' )
        $nApproved += 1;

    else if( $r[ 'status' ] == 'REJECTED' )
        $nRejected += 1;

    else if( $r[ 'status' ] == 'CANCELLED' )
        $nCancelled += 1;
    else 
        $nOther += 1;

    // Time take to approve a request, in hours
    if( $r[ 'last_modified_on' ] )
    {
        $time = strtotime( $r['date'] . ' ' . $r[ 'start_time' ] ) 
                    - strtotime( $r['last_modified_on'] );
        $time = $time / (24 * 3600.0);
        array_push( $timeForAction, array($time, 1) ); 
    }
}

// rate per day.
$rateOfRequests = 24 * 3600.0 * count( $requests ) / (1.0 * $timeInterval);

/*
 * Venue usage timne.
 */
$events = getTableEntries( 'events', 'date'
                , "status='VALID' AND date >= '2017-02-28' AND date < '$upto'" );

$venueUsageTime = array( );
// How many events, as per class.
$eventsByClass = array( );

foreach( $events as $e )
{
    $time = (strtotime( $e[ 'end_time' ] ) - strtotime( $e[ 'start_time' ] ) ) / 3600.0;
    $venue = $e[ 'venue' ];

    $venueUsageTime[ $venue ] = __get__( $venueUsageTime, $venue, 0.0 ) + $time;
    $eventsByClass[ $e[ 'class' ] ] = __get__( $eventsByClass, $e['class'], 0 ) + 1;
}

// AWS to this list.
$eventsByClass[ 'ANNUAL WORK SEMINAR' ] = count( 
    getTableEntries( 'annual_work_seminars', 'date', "date>'2017-03-21'" ) );

// Add courses events generated by Hippo.
$eventsByClass[ 'CLASS' ] = __get__( $eventsByClass, 'CLASS', 0 ) 
    + totalClassEvents( );

$eventsByClassPie = array( );
foreach( $eventsByClass as $cl => $v )
    $eventsByClassPie[ ] = array( 'name' => $cl, 'y' => $v );

$venues = array_keys( $venueUsageTime );
$venueUsage = array_values( $venueUsageTime );
$venueUsagePie = array( );
foreach( $venueUsageTime as $v => $t )
    $venueUsagePie[ ] = array( "name" => $v, "y" => $t );

$bookingTable = "<table border='1'>
    <tr> <td>Total booking requests</td> <td>" . count( $requests ) . "</td> </tr>
    <tr> <td>Rate of booking (# per day)</td> <td>" 
            .   number_format( $rateOfRequests, 2 ) . "</td> </tr>
    <tr> <td>Approved requests</td> <td> $nApproved </td> </tr>
    <tr> <td>Rejected requests</td> <td> $nRejected </td> </tr>
    <tr> <td>Pending requests</td> <td> $nPending </td> </tr>
    <tr> <td>Cancelled by user</td> <td> $nCancelled </td> </tr>
    </table>";

$thesisSeminars = getTableEntries( 'talks', 'class', "class='THESIS SEMINAR'" );
$thesisSemPerYear = array( );
$thesisSemPerMonth = array( );

for( $i = 1; $i <= 12; $i ++ )
    $thesisSemPerMonth[ date( 'F', strtotime( "2000/$i/01" ) )] = 0;

foreach( $thesisSeminars as $ts )
{
    // Get event of this seminar.
    $event = getEventsOfTalkId( $ts[ 'id' ] );

    $year = intval( date( 'Y', strtotime( $event['date'] )  ));
    $month = date( 'F', strtotime( $event['date'] ) );


    if( $year > 2000 )
        $thesisSemPerYear[ $year ] = __get__( $thesisSemPerYear, $year, 0 ) + 1;

    $thesisSemPerMonth[ $month ] += 1;

}
?>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var venueUsage = <?php echo json_encode( $venueUsage ); ?>;
    var venueUsagePie = <?php echo json_encode( $venueUsagePie ); ?>;
    var venues = <?php echo json_encode( $venues ); ?>;

    Highcharts.chart('venue_usage1', {
        chart : { type : 'column' },
        title: { text: 'Venue usage in hours' },
        yAxis: { title: { text: 'Time in hours' } },
        xAxis : { categories : venues }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Venue usage', data: venueUsage
                    , pointPlacement: 'middle',
                    , showInLegend:false 
                 }], 
        });

    Highcharts.chart('venue_usage2', {
        chart : { type : 'pie' },
        title: { text: 'Venue usage' },
        tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>' },
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Venue usage'
                    , data: venueUsagePie
                    , showInLegend:false }], 
    });

});
</script>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var eventsByClass = <?php echo json_encode( array_values( $eventsByClass) ); ?>;
    var eventsByClassPie = <?php echo json_encode( $eventsByClassPie ); ?>;
    var cls = <?php echo json_encode( array_keys( $eventsByClass) ); ?>;

    Highcharts.chart('events_class1', {

        chart : { type : 'column' },
        title: { text: 'Event distribution by categories' },
        yAxis: { title: { text: 'Number of events' } },
        xAxis : { categories : cls }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Total events grouped by class'
                    , data: eventsByClass, showInLegend:false
                 }], 
    });

    Highcharts.chart('events_class2', {
        chart : { type : 'pie' },
        title: { text: 'Event distribution' },
        tooltip: { pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>' },
        series: [{ name: 'Total events grouped by class'
                    , data: eventsByClassPie, showInLegend:false 
                }], 
    });

});

</script>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var thesisSemPerMonth = <?php echo json_encode( array_values( $thesisSemPerMonth) ); ?>;
    var cls = <?php echo json_encode( array_keys( $thesisSemPerMonth) ); ?>;

    Highcharts.chart('thesis_seminar_per_month', {

        chart : { type : 'column' },
        title: { text: 'Thesis Seminar distributuion (monthly)' },
        yAxis: { title: { text: 'Number of events' } },
        xAxis : { categories : cls }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Total Thesis Seminars', data: thesisSemPerMonth
            ,  showInLegend:false
            , pointPlacement: 'middle',
        }], 
    });

});
</script>

<script type="text/javascript" charset="utf-8">
$(function( ) { 

    var thesisSemPerYear = <?php echo json_encode( array_values( $thesisSemPerYear) ); ?>;
    var cls = <?php echo json_encode( array_keys( $thesisSemPerYear) ); ?>;

    Highcharts.chart('thesis_seminar_per_year', {

        chart : { type : 'column' },
        title: { text: 'Thesis Seminar distributuion (yearly)' },
        yAxis: { title: { text: 'Number of events' } },
        xAxis : { categories : cls }, 
        legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle' },
        series: [{ name: 'Total Thesis Seminars', data: thesisSemPerYear, showInLegend : false}], 
    });

});

</script>

<?php 

$awses = getAllAWS( );
$speakers = getAWSSpeakers( );

// Construct a pie-data to be fed into Hightcharts.
$awsSpeakers = array( );
foreach( $speakers as $speaker )
{
    $pi = getPIOrHost( $speaker[ 'login' ] ); 
    $spec = getSpecialization( $speaker[ 'login' ], $pi );
    $awsSpeakers[ $spec ] = __get__( $awsSpeakers, $spec, 0 ) + 1;
}
$awsSpeakersPie = array( );
foreach( $awsSpeakers as $spec => $v )
    $awsSpeakersPie[] = array( 'name' => $spec, 'y' => $v );

$awsPerSpeaker = array( );

$awsYearData = array_map(
    function( $x ) { return array(date('Y', strtotime($x['date'])), 0); } 
    , $awses
    );

// Here each valid AWS speaker initialize her count to 0.
foreach( $speakers as $speaker )
    $awsPerSpeaker[ $speaker['login'] ] = array();

// If there is already an AWS for a speaker, add to her count.
foreach( $awses as $aws )
{
    $speaker = $aws[ 'speaker' ];
    if( ! array_key_exists( $speaker, $awsPerSpeaker ) )
        $awsPerSpeaker[ $speaker ] = array();

    array_push( $awsPerSpeaker[ $speaker ], $aws );
}

$awsCounts = array( );
$awsCountsBySpec = array( );
$awsDates = array( );
foreach( $awsPerSpeaker as $speaker => $awses )
{
    $pi = getPIOrHost( $speaker );
    $awsCounts[ $speaker ] = count( $awses );
    $awsDates[ $speaker ] = array_map( 
        function($x) { return $x['date']; }, $awses 
    );

    // Get the AWS specialization by queries the specialization of PI. If not
    // found, use the current specialization of speaker.
    foreach( $awses as $aws )
    {
        $spec = getFacultySpecialization( $aws[ 'supervisor_1' ] );
        if( ! trim( $spec ) )
            $spec = getSpecialization( $speaker, $pi );
        if( $spec != 'UNSPECIFIED' )
            $awsCountsBySpec[ $spec ] = __get__( $awsCountsBySpec, $spec, 0) + 1;
    }
}
$awsCountsBySpecPie = array( );
foreach( $awsCountsBySpec as $spec => $v )
    $awsCountsBySpecPie[] = array( 'name' => $spec, 'y' => $v );

$numAWSPerSpeaker = array( );
$gapBetweenAWS = array( );
foreach( $awsCounts as $key => $val )
{
    array_push( $numAWSPerSpeaker,  array($val, 0) );
    for( $i = 1; $i < count( $awsDates[ $key ] ); $i++ )
    {
        $gap = (strtotime( $awsDates[ $key ][$i-1] ) - 
            strtotime( $awsDates[ $key ][$i]) )/ (30.5 * 86400);

        // We need a tuple. Second entry is dummy. Only push if the AWS was
        array_push( $gapBetweenAWS, array( $gap, 0 ) );
    }
}


?>


<script type="text/javascript" charset="utf-8">
$(function () {
    
    var data = <?php echo json_encode( $awsYearData ); ?>;
    var speakers = <?php echo json_encode( $awsSpeakersPie); ?>;

    /**
     * Get histogram data out of xy data
     * @param   {Array} data  Array of tuples [x, y]
     * @param   {Number} step Resolution for the histogram
     * @returns {Array}       Histogram data
     */
    function histogram(data, step) {
        var histo = {},
            x,
            i,
            arr = [];

        // Group down
        for (i = 0; i < data.length; i++) {
            x = Math.floor(data[i][0] / step) * step;
            if (!histo[x]) {
                histo[x] = 0;
            }
            histo[x]++;
        }

        // Make the histo group into an array
        for (x in histo) {
            if (histo.hasOwnProperty((x))) {
                arr.push([parseFloat(x), histo[x]]);
            }
        }

        // Finally, sort the array
        arr.sort(function (a, b) {
            return a[0] - b[0];
        });

        return arr;
    }

    Highcharts.chart('aws_per_year', {
        chart: { type: 'column' },
        title: { text: 'Number of Annual Work Seminars per year' },
        xAxis: { min : 2010 },
        yAxis: [ { title: { text: 'AWS Count' } }, ],
        series: [{
            name: 'AWS this year',
            type: 'column',
            data: histogram(data, 1),
            pointPadding: 0,
            groupPadding: 0,
            pointPlacement: 'middle',
            showInLegend:false,
        }, 
    ] });

    Highcharts.chart('aws_speakers_pie', {
        chart: { type: 'pie' },
        title: { text: 'Size of each Subject Group' },
        series: [{ name: 'Number of AWS speakers'
            , data: speakers, },] }
    );

});

</script>


<script type="text/javascript" charset="utf-8">
$(function () {
    
    var data = <?php echo json_encode( $numAWSPerSpeaker ); ?>;

    /**
     * Get histogram data out of xy data
     * @param   {Array} data  Array of tuples [x, y]
     * @param   {Number} step Resolution for the histogram
     * @returns {Array}       Histogram data
     */
    function histogram(data, step) {
        var histo = {},
            x,
            i,
            arr = [];

        // Group down
        for (i = 0; i < data.length; i++) {
            x = Math.floor(data[i][0] / step) * step;
            if (!histo[x]) {
                histo[x] = 0;
            }
            histo[x]++;
        }

        // Make the histo group into an array
        for (x in histo) {
            if (histo.hasOwnProperty((x))) {
                arr.push([parseFloat(x), histo[x]]);
            }
        }

        // Finally, sort the array
        arr.sort(function (a, b) {
            return a[0] - b[0];
        });

        return arr;
    }

    Highcharts.chart('aws_chart1', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Speakers distrbution'
        },
        xAxis: { min : -0.5, title: {text: '#AWS given'} },
        yAxis: [{ title: { text: 'Speaker Count' }
        }, ],
        series: [{
            name: 'Number of speakers',
            type: 'column',
            data: histogram(data, 1),
            pointPadding: 0.1,
            groupPadding: 0,
            pointPlacement: 'middle'
        }, 
    ] });

});

</script>


<script>

$(function () {
    
    var data = <?php echo json_encode( $awsCountsBySpecPie ); ?>;
    Highcharts.chart('aws_gap_chart', {
        chart: { type: 'pie' },
        title: { text: 'AWS by Subject Area' },
        series: [{
            name: 'Number of AWS',
            data: data,
        }, 
    ] });

});
</script>

<h1>Academic statistics since March 01, 2017</h1>

<h3>Annual Work Seminars Distributions</h3>
<table class=chart>
<tr> <td> <div id="aws_per_year"></div> </td>
<td> <div id="aws_gap_chart"></div> </td>
</tr> 
</table>

<h3>AWS Speakers distributions</h3>
<table class=chart>
<tr> <td> <div id="aws_chart1"></div> </td>
<td> <div id="aws_speakers_pie"></div> </td>
</tr> </table>

<h3>Thesis seminar distributions</h3>
<table class=chart>
<tr> <td> <div id="thesis_seminar_per_month"></div> </td>
<td> <div id="thesis_seminar_per_year"></div> </td>
</tr> </table>

<h1>Booking requests between <?php
    echo humanReadableDate( 'march 01, 2017') ?> 
    and <?php echo humanReadableDate( $upto ); ?></h1>

<?php 
echo $bookingTable;
?>

<h1>Venue usage between <?php
    echo humanReadableDate( 'march 01, 2017') ?> 
    and <?php echo humanReadableDate( $upto ); ?></h1>

<h3></h3>
<table class="chart">
<tr> <td> <div id="venue_usage1"></div> </td>
    <td> <div id="venue_usage2" ></div> </td>
</tr>
</table>

<h3></h3>

<table class="chart">
<tr> <td> <div id="events_class1"></div> </td>
    <td> <div id="events_class2" ></div> </td>
</tr>
</table>


<a href="javascript:window.close();">Close Window</a>

