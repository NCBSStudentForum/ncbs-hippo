"""db_connect.py: 

Connect to database

"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2016, Dilawar Singh"
__credits__          = ["NCBS Bangalore"]
__license__          = "GNU GPL"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
import mysql.connector
import mysql
import ConfigParser
from logger import _logger

config = ConfigParser.ConfigParser( )
thisdir = os.path.dirname( os.path.realpath( __file__ ) )
config.read( os.path.join( '/etc', 'hipporc' ) )
_logger.debug( 'Read config file %s' % str( config ) )

class MySQLCursorDict(mysql.connector.cursor.MySQLCursor):
    def _row_to_python(self, rowdata, desc=None):
        row = super(MySQLCursorDict, self)._row_to_python(rowdata, desc)
        if row:
            return dict(zip(self.column_names, row))
        return None

user = config.get( 'mysql', 'user' )
passwd = config.get( 'mysql', 'password' ).replace( '"', '')
db_ = mysql.connector.connect( 
        host = config.get( 'mysql', 'host' ) 
        , user = user , password = passwd , db = 'hippo'
        )
