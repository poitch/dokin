<?php
define('DOKIN_DIR', dirname(dirname(__FILE__)).'/');
require_once DOKIN_DIR.'Model.php';

class Person extends Model
{
    protected $sDatabase = 'dokin';
    protected $sTable = 'people';
    protected $sIdField = '_id';
    protected $aFields = array('_id', 'firstName', 'lastName');
}


require_once 'PHPUnit/Framework.php';

class MongoTest extends PHPUnit_Framework_TestCase
{
    public function testInsertWithExec()
    {
        $oDriver = DBDriver::get_instance('mongo');
        $oPerson = new Person();
        $oPerson->firstName = 'Jerome';
        $oPerson->lastName = 'Poichet';
        $mRes = $oPerson->insert()->exec($oDriver);
        $this->assertNotNull($mRes);
        $this->assertNotNull($oPerson->_id);
        $this->assertNotNull($mRes->_id);

        $mRes = $oPerson->delete()->exec($oDriver);
        $this->assertEquals(true, $mRes);
    }

    public function testInsertWithoutExec()
    {
        $oDriver = DBDriver::get_instance('mongo');
        $oPerson = new Person();
        $oPerson->firstName = 'Jerome';
        $oPerson->lastName = 'Poichet';
        $mRes = $oPerson->insert($oDriver);
        $this->assertNotNull($mRes);
        $this->assertNotNull($oPerson->_id);
        $this->assertNotNull($mRes->_id);

        $mRes = $oPerson->delete($oDriver);
        $this->assertEquals(true, $mRes);
    }

    public function testUpdateWithExec()
    {
        $oDriver = DBDriver::get_instance('mongo');

        Person::delete('Person')->where('firstName', 'Tadao')->exec($oDriver);

        $oPerson = new Person();
        $oPerson->firstName = 'Jerome';
        $oPerson->lastName = 'Poichet';
        $mRes = $oPerson->insert()->exec($oDriver);
        $this->assertNotNull($mRes);
        $this->assertNotNull($oPerson->_id);
        $this->assertNotNull($mRes->_id);

        // Update
        $oPerson->firstName = 'Tadao';
        $mRes = $oPerson->update()->exec($oDriver);
        $this->assertNotNull($mRes);
        $this->assertEquals('Tadao', $oPerson->firstName);

        // Find back
        $oRes = Person::select('Person')->where('firstName', 'Tadao')->exec($oDriver);
        $oOther = $oRes->fetch();
        $this->assertEquals($oPerson->_id, $oOther->_id);
        $this->assertEquals('Tadao', $oOther->firstName);

        $mRes = $oPerson->delete($oDriver);
        $this->assertEquals(true, $mRes);
    }


}

