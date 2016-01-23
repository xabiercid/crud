<?php
namespace Anavel\Crud\Tests\Abstractor\Eloquent;

use Anavel\Crud\Abstractor\Eloquent\Relation\MiniCrud;
use Anavel\Crud\Abstractor\Eloquent\Relation\MiniCrudPolymorphic;
use Anavel\Crud\Abstractor\Eloquent\Relation\Select;
use Anavel\Crud\Tests\Models\User;
use Anavel\Crud\Tests\TestBase;
use Mockery;
use Mockery\Mock;
use phpmock\mockery\PHPMockery;


class MiniCrudPolymorphicTest extends TestBase
{
    /** @var  MiniCrudPolymorphic */
    protected $sut;
    /** @var  Mock */
    protected $relationMock;
    /** @var  Mock */
    protected $modelManagerMock;
    /** @var  Mock */
    protected $fieldFactoryMock;
    /** @var  Mock */
    protected $modelAbstractorMock;

    protected $wrongConfig;
    protected $getClassMock;

    public function setUp()
    {
        parent::setUp();

        $this->wrongConfig = require __DIR__ . '/../../wrong-config.php';

        $this->relationMock = $this->mock('Illuminate\Database\Eloquent\Relations\Relation');
        $this->fieldFactoryMock = $this->mock('Anavel\Crud\Contracts\Abstractor\FieldFactory');
        $this->modelManagerMock = Mockery::mock('ANavallaSuiza\Laravel\Database\Contracts\Manager\ModelManager');

        $this->getClassMock = PHPMockery::mock('Anavel\Crud\Abstractor\Eloquent\Relation\Traits',
            'get_class');

        \App::instance('Anavel\Crud\Contracts\Abstractor\ModelFactory', $modelFactoryMock = $this->mock('Anavel\Crud\Contracts\Abstractor\ModelFactory'));
        $modelFactoryMock->shouldReceive('getByClassName')->andReturn($this->modelAbstractorMock = $this->mock('Anavel\Crud\Contracts\Abstractor\Model'));
        $this->relationMock->shouldReceive('getRelated')->andReturn($this->relationMock);
    }

    public function buildRelation()
    {
        $config = require __DIR__ . '/../../config.php';
        $this->sut = new MiniCrudPolymorphic(
            $config['Users']['relations']['group'],
            $this->modelManagerMock,
            $user = new User(),
            $this->relationMock,
            $this->fieldFactoryMock
        );
    }

    public function test_implements_relation_interface()
    {
        $this->getClassMock->andReturn('Illuminate\Database\Eloquent\Relations\MorphMany');

        $this->buildRelation();
        $this->assertInstanceOf('Anavel\Crud\Contracts\Abstractor\Relation', $this->sut);
    }

    public function test_throws_exception_when_class_not_compatible()
    {
        $this->setExpectedException('Anavel\Crud\Abstractor\Exceptions\RelationException');
        $this->getClassMock->andReturn('chompy');
        $this->buildRelation();
    }


    public function test_get_edit_fields_returns_array()
    {
        $this->relationMock->shouldReceive('getRelated', 'getPlainForeignKey', 'getPlainMorphType', 'getParent',
            'getKeyName')->andReturn($this->relationMock);
        $this->relationMock->shouldReceive('getResults')->andReturn(collect([$postMock = $this->mock('Anavel\Crud\Tests\Models\Post')]));
        $this->modelManagerMock->shouldReceive('getAbstractionLayer')->andReturn($dbalMock = $this->mock('\ANavallaSuiza\Laravel\Database\Contracts\Dbal\AbstractionLayer'));
        $dbalMock->shouldReceive('getTableColumns')->andReturn([$columnMock = $this->mock('Doctrine\DBAL\Schema\Column')]);
        $postMock->shouldReceive('getAttribute')->andReturn('chompy');


        $this->fieldFactoryMock->shouldReceive('setColumn', 'setConfig')->andReturn($this->fieldFactoryMock);
        $this->fieldFactoryMock->shouldReceive('get')->andReturn($fieldMock = $this->mock('Anavel\Crud\Contracts\Abstractor\Field'));
        $fieldMock->shouldReceive('setOptions');

        $fieldMock->shouldReceive('setValue')->times(1);

        $this->modelAbstractorMock->shouldReceive('getRelations')->times(1)->andReturn([$this->secondaryRelationMock = $this->mock('Anavel\Crud\Abstractor\Eloquent\Relation\Select')]);
        $this->secondaryRelationMock->shouldReceive('getEditFields')->andReturn([]);



        $this->getClassMock->andReturn('Illuminate\Database\Eloquent\Relations\MorphMany');
        $this->buildRelation();
        $fields = $this->sut->getEditFields();

        $this->assertInternalType('array', $fields, 'getEditFields should return an array');
        $this->assertCount(2, $fields);
        $this->assertInstanceOf('Anavel\Crud\Contracts\Abstractor\Field', $fields[0]);
    }

    public function test_persist_with_no_old_results()
    {
        $inputArray = [
            '0' => [
                'field'          => 1,
                'otherField'     => 3,
                'someOtherField' => 3,
            ]
        ];
        $requestMock = $this->mock('Illuminate\Http\Request');
//
        $requestMock->shouldReceive('input')->with('group')->atLeast()->once()->andReturn($inputArray);

        $this->relationMock->shouldReceive('getForeignKey', 'getPlainMorphType', 'getMorphClass');
        $this->relationMock->shouldReceive('getRelated', 'getParent', 'get')->andReturn($this->relationMock);
        $this->relationMock->shouldReceive('keyBy')->once()->andReturn(collect());
        $this->relationMock->shouldReceive('getKeyName')->andReturn('id');
        $this->relationMock->shouldReceive('newInstance')->andReturn($modelMock = $this->mock('Anavel\Crud\Tests\Models\Post'));

        $modelMock->shouldReceive('getKey')->andReturn(1);
        $modelMock->shouldReceive('setAttribute')->times(5);
        $modelMock->shouldReceive('save')->times(1);

        $this->getClassMock->andReturn('Illuminate\Database\Eloquent\Relations\MorphMany');
        $this->buildRelation();

        $fields = $this->sut->persist($requestMock);
    }

    public function test_persist_with_old_results()
    {
        $inputArray = [
            '0' => [
                'id'             => 1,
                'otherField'     => 3,
                'someOtherField' => 3,
            ],
            '1' => [
                'id'             => 1,
                'otherField'     => 3,
                'someOtherField' => 3,
            ]
        ];
        $requestMock = $this->mock('Illuminate\Http\Request');
//
        $requestMock->shouldReceive('input')->with('group')->atLeast()->once()->andReturn($inputArray);

        $this->relationMock->shouldReceive('getForeignKey', 'getPlainMorphType', 'getMorphClass');
        $this->relationMock->shouldReceive('getRelated', 'getParent', 'get')->andReturn($this->relationMock);
        $this->relationMock->shouldReceive('newInstance');
        $this->relationMock->shouldReceive('keyBy')->once()->andReturn(collect([1 => $modelMock = $this->mock('Anavel\Crud\Tests\Models\Post')]));
        $this->relationMock->shouldReceive('getKeyName')->andReturn('id');

        $modelMock->shouldReceive('getKey')->andReturn(1);
        $modelMock->shouldReceive('setAttribute')->times(10);
        $modelMock->shouldReceive('save')->times(2);

        $this->getClassMock->andReturn('Illuminate\Database\Eloquent\Relations\MorphMany');
        $this->buildRelation();

        $fields = $this->sut->persist($requestMock);
    }
}
