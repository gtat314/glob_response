<?php




/**
 * @global int NUM_DAYS_DELETE_OBSOLETE_RESPONSES
 */
class glob_response extends glob_dbaseTablePrimary {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $time;

    /**
     * @var string|null
     */
    public $action;

    /**
     * @var string|null
     */
    public $authuser;

    /**
     * @var string|null
     */
    public $payload;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string|null
     */
    public $response = [];

    /**
     * @var int|null
     */
    public $responseCode;




    /**
     * @global PDO $pdo
     * @global int NUM_DAYS_DELETE_OBSOLETE_RESPONSES
     * @static
     * @return void
     */
    public static function db_deleteObsolete() {

        global $pdo;

        $numDaysDeleteObsoleteResponses = 90;

        if ( defined( 'NUM_DAYS_DELETE_OBSOLETE_RESPONSES' ) ) {

            $numDaysDeleteObsoleteResponses = NUM_DAYS_DELETE_OBSOLETE_RESPONSES;

        }

        $query = 'DELETE FROM `' . __CLASS__ . '` WHERE time < DATE_SUB( NOW(), INTERVAL ' . $numDaysDeleteObsoleteResponses . ' DAY);';

        $stmt = $pdo->prepare( $query );

        $stmt->execute();

    }

    /**
     * @static
     * @global PDO $pdo
     * @return glob_response[]|false
     */
    public static function getAllToday() {

        global $pdo;

        $stmt = $pdo->prepare(
            'SELECT *
            FROM ' . __CLASS__ . '
            WHERE `time` > DATE_SUB( NOW(), INTERVAL 24 HOUR )
            ORDER BY id DESC'
        );

        $stmt->execute();

        return $stmt->fetchAll( PDO::FETCH_CLASS, __CLASS__ );

    }

    /**
     * @static
     * @global PDO $pd
     * @param string $status
     * @param int|null @responseCode
     * @param mixed|null $response
     * @return void
     */
    public static function insertDB( $status, $responseCode = null, $response = null ) {

        global $pdo;

        $action = null;

        if ( isset( $_POST[ 'action' ] ) === true ) {

            $action = $_POST[ 'action' ];

        }

        $authuser = null;

        if ( isset( $_POST[ 'authuser' ] ) === true ) {

            $authuser = $_POST[ 'authuser' ];

        }

        $payload = [
            'SERVER' => $_SERVER,
            'POST' => $_POST,
            'GET' => $_GET
        ];

        $stmt = $pdo->prepare('INSERT INTO ' . __CLASS__ . ' VALUES ( NULL, NOW(), ?, ?, ?, ?, ?, ? )');

        $stmt->execute([ $action, $authuser, json_encode( $payload ), $status, json_encode( $response ), $responseCode ]);

    }

    /**
     * @static
     * @param int $errorCode
     * @return void
     */
    public static function broadcastError( $errorCode ) {

        $toRespond = [
            'code' => $errorCode
        ];

        self::insertDB( '200', $errorCode, $toRespond );

        echo json_encode( $toRespond );

        exit( 0 );

    }

    /**
     * @static
     * @param int $blockedIp
     * @return void
     */
    public static function broadcast403( $blockedIp = 0 ) {

        self::insertDB( '403', null, debug_backtrace() );

        http_response_code( 403 );

        echo json_encode([
            'code' => $blockedIp
        ]);

        exit( 0 );

    }

    /**
     * @static
     * @param mixed $errorData
     * @return void
     */
    public static function broadcast500( $errorData ) {

        self::insertDB( '500', null, $errorData );

        http_response_code( 500 );

        echo json_encode([
            'code' => 500
        ]);

        exit( 0 );

    }

    /**
     * @param int           $code
     * @param string|null   $msg
     * @param boolean       $echoJson
     * @param string|null   $uri
     * @param boolean       $exit
     */
    public static function httpResponseCode( $code, $msg = null, $echoJson = true, $uri = null, $exit = true ) {

        self::insertDB( $code, null, $msg );

        http_response_code( $code );

        if ( $echoJson === true ) {

            echo json_encode([
                'code' => $code
            ]);

        }

        if ( $uri !== null ) {

            include( $uri );

        }

        if ( $exit === true ) {

            exit( 0 );

        }

    }

    public static function broadcastMessage( $code, $message, $recordFlag = true ) {

        $toRespond = [
            'code' => $code,
            'response' => $message
        ];

        if ( $recordFlag === true ) {

            self::insertDB( $code, null, $toRespond );

        }

        echo json_encode( $toRespond );

        exit( 0 );

    }



    
    /**
     * @return glob_response
     */
    public function __construct() {

        return $this;

    }

    /**
     * @param string $label
     * @param string $value
     * @return glob_response
     */
    public function add( $label, $value ) {

        $this->response[ $label ] = $value;

        return $this;

    }

    /**
     * @param string $label
     * @param array $value
     * @return glob_response
     */
    public function addArrayOfObjects( $label, Array $value ) {

        $this->response[ $label ] = [];

        for ( $i = 0 ; $i < count( $value ) ; $i++ ) {

            $this->response[ $label ][] = get_object_vars( $value[ $i ] );

        }

        return $this;

    }

    /**
     * @param string $value
     * @return glob_response
     */
    public function addHash( $value ) {

        $this->response[ 'hash' ] = hash( 'md5', json_encode( $value ) );

        return $this;

    }

    /**
     * @param string $label
     * @param string $value
     * @return glob_response
     */
    public function addNamedHash( $label, $value ) {

        $this->response[ $label ] = hash( 'md5', json_encode( $value ) );

        return $this;

    }

    /**
     * @global int NUM_DAYS_DELETE_OBSOLETE_RESPONSES
     * @param boolean $recordFlag
     * @return void
     */
    public function broadcast( $recordFlag = true ) {

        if ( defined( 'NUM_DAYS_DELETE_OBSOLETE_RESPONSES' ) ) {

            if ( mt_rand( 1, 100 ) === 1 ) {

                self::db_deleteObsolete();

            }

        }

        $toRespond = [
            'code' => 0,
            'response' => $this->response
        ];

        if ( $recordFlag === true ) {

            self::insertDB( '200', 0, $toRespond );

        }

        echo json_encode( $toRespond );

        exit( 0 );

    }

}