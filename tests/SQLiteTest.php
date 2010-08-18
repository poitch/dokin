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

        $db = sqlite_open(dirname(__FILE__).'/unittest.db');
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

            $res = sqlite_query($db, 'INSERT INTO people (id,firstName,lastName) VALUES (3, \'A\', \'B\')', $sError);
            if ($res === false) {
                throw new Exception($sError);
            }

             $res = sqlite_query($db, 'INSERT INTO people (id,firstName,lastName) VALUES (4, \'C\', \'D\')', $sError);
            if ($res === false) {
                throw new Exception($sError);
            }
 
        }
        sqlite_close($db);
    }

    public function testInsert()
    {
        $this->prepareDB(false);
        $oDriver = DBDriver::get_instance('sqlite', array('filename' => dirname(__FILE__).'/unittest.db'));

        $oPerson = new Person();
        $oPerson->firstName = 'Jerome';
        $oPerson->lastName = 'Poichet';
        $mRes = $oPerson->insert($oDriver);

        $mRes = Person::select('Person')->where('firstName', 'Jerome')->exec($oDriver);
        $this->assertEquals(1, $mRes->numRows());

        $oOther = $mRes->fetch();
        $this->assertEquals('Person', get_class($oOther));
        $this->assertNotNull($oOther->id);

        $this->assertEquals($oPerson->id, $oOther->id);
    }

    public function testUpdate()
    {
        $this->prepareDB(true);
        $oDriver = DBDriver::get_instance('sqlite', array('filename' => dirname(__FILE__).'/unittest.db'));

        $mRes = Person::select('Person')->where('lastName', 'Piochet')->exec($oDriver);
        $this->assertEquals(1, $mRes->numRows());

        $oPerson = $mRes->fetch();
        $this->assertNotNull($oPerson->id);
        $this->assertEquals('Piochet', $oPerson->lastName);

        $oPerson->lastName = 'Poichet';
        $mRes = $oPerson->update()->exec($oDriver);


        $mRes = Person::select('Person')->where('lastName', 'Piochet')->exec($oDriver);
        $this->assertEquals(0, $mRes->numRows());

        $mRes = Person::select('Person')->where('firstName', 'Jerome')->where('lastName', 'Poichet')->exec($oDriver);
        $this->assertEquals(1, $mRes->numRows());
        $oOther = $mRes->fetch();
        $this->assertEquals($oPerson->id, $oOther->id);
    }

    public function testUpdateCriteria()
    {
        $this->prepareDB(true);
        $oDriver = DBDriver::get_instance('sqlite', array('filename' => dirname(__FILE__).'/unittest.db'));

        $mRes = Person::select('Person')->where('lastName', 'B')->exec($oDriver);
        $this->assertEquals(1, $mRes->numRows());


        $mRes = Person::update('Person')->set('lastName', 'Poichet')->where('lastName', 'B')->exec($oDriver);

        $mRes = Person::select('Person')->where('lastName', 'B')->exec($oDriver);
        $this->assertEquals(0, $mRes->numRows());
        $mRes = Person::select('Person')->where('lastName', 'Poichet')->exec($oDriver);
        $this->assertEquals(2, $mRes->numRows());
    }

    public function testDeleteSingle()
    {
        $this->prepareDB(true);
        $oDriver = DBDriver::get_instance('sqlite', array('filename' => dirname(__FILE__).'/unittest.db'));

        $oPerson = Person::get_instance('Person', 1, $oDriver);
        $this->assertNotNull($oPerson);
        $this->assertEquals(1, $oPerson->id);

        $oPerson->delete($oDriver);
    }

    public function testDeleteCriteria()
    {
        $this->prepareDB(true);
        $oDriver = DBDriver::get_instance('sqlite', array('filename' => dirname(__FILE__).'/unittest.db'));

        $mRes = Person::delete('Person')->where('lastName', 'Piochet')->exec($oDriver);
        $mRes = Person::select('Person')->where('lastName', 'Piochet')->exec($oDriver);
        $this->assertEquals(0, $mRes->numRows());
    }


}

