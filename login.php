<?php 

include_once( "header.php" );
include_once( "methods.php" );
include_once( "database.php" );
include_once( "ldap.php" );

$conf = $_SESSION['conf'];
$ldap = $_POST['username'];
$pass = $_POST['pass'];

$_SESSION['AUTHENTICATED'] = FALSE;

/* continue */
$conn = imap_open( "{imap.ncbs.res.in:993/ssl/readonly}INBOX", $ldap, $pass, OP_HALFOPEN );
if( ! $conn )
   $conn = imap_open( "{mail.instem.res.in:993/ssl/readonly}INBOX", $ldap, $pass, OP_HALFOPEN );

if(!$conn) 
{
    echo printErrorSevere("FATAL : Username or password is incorrect.");
    goToPage( 'index.php', 2 );
}

else 
{
    echo printInfo( "Login successful" );
    imap_close( $conn );
    $_SESSION['AUTHENTICATED'] = TRUE;
    $_SESSION['user'] = $ldap;

    echo "Quering LDAP server for user information";
    $ldapInfo = getUserInfoFromLdap( $ldap );
    if( $ldapInfo )
        createUserOrUpdateLogin( $ldap, $ldapInfo );
    else
    {
        echo printWarning( "Could not query LDAP server" );
        goToPage( "user.php", 1 );
    }


    // If user title is unspecified then redirect him/her to edit user info
    $userInfo = getUserInfo( $ldap );
    if( $userInfo['title'] == 'UNSPECIFIED' )
    {
       echo printInfo( "Your details are missing essential information I need to
       continue ... " );
       goToPage( "user_info.php", 3 );
       exit;
    }

    goToPage( "user.php", 0 );
    exit;
}

?>
