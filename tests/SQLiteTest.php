<?php
define('DOKIN_DIR', dirname(dirname(__FILE__)).'/');
require_once DOKIN_DIR.'Model.php';

class Person extends Model
{
    protected $sDatabase = 'dokin';
    protected $sTable = 'people';
    protected $sIdField = 'id';
    protected $aFields = array('id', 'firstName', 'lastName');
}


require_once 'PHPUnit/Framework.php';

class SQLiteTest extends PHPUnit_Framework_TestCase
{
    public function prepareDB($bWithData = true)
    {
        if (file_exists(dirname(__FILE__).'/unittest.db')) {
            unlink(dirname(__FILE__).'/unittest.db');
        }

        $db = sqlite_open('test.db');
        $res = sqlite_query($db, 'CREATE TABLE people (id INTEGER PRIMARY KEY, firstName TEXT, lastName TEXT)', $sError);
        if ($res === false) {
            throw new Exception($sError);
        }

        if ($bWithData) {
            $res = sqlite_query($db, 'INSERT INTO people (id,firstName,lastName) VALUES (1, \'Jerome\', \'Piochet\')', $sError);
            if ($res === false) {
                throw new Exception($sError);
            }
    
            $res = sqlite_query($db, 'INSERT INTO people (id,firstName,lastName) VALUES (2, \'Tadao\', \'Poichet\')', $sError);
            if ($res === false) {
                throw new Exception($sError);
            }
        }
        sqlite_close($db);
    }

    public function testInsert()
    {
        $this->prepareDB(false);
        $oDriver = DBDriver::get_instance('sqlite', array('filename' => 'test.db'));

        $oPerson = new Person();
        $oPerson->firstName = 'Jerome';
        $oPerson->lastName = 'Poichet';
        $mRes = $oPerson->insert($oDriver);

        $oRes = Person::select('Person')->where('firstName', 'Jerome')->exec($oDriver);
        $this->assertEquals(1, $oRes->numRows());

        $oOther = $oRes->fetch();
        $this->assertEquals('Person', get_class($oOther));
        $this->assertNotNull($oOther->id);

        $this->assertEquals($oPerson->id, $oOther->id);
    }


}

