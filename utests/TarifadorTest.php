<?php

namespace FreePBX {
    if (!class_exists('FreePBX_Helpers')) {
        class FreePBX_Helpers {
            public function getReq($var, $default = '') { return $_REQUEST[$var] ?? $default; }
        }
    }
    if (!interface_exists('BMO')) {
        interface BMO {}
    }

    if (!class_exists('Database')) {
        class Database {
            public function query($sql) {}
            public function prepare($sql) {}
        }
    }
}

namespace {

    use PHPUnit\Framework\TestCase;
    use FreePBX\modules\Tarifador;
    use FreePBX\Database;

    $baseDir = __DIR__ . '/../';
    
    require_once $baseDir . 'Utils/Sanitize.php';
    require_once $baseDir . 'Traits/CallTrait.php';
    require_once $baseDir . 'Traits/RateTrait.php';
    require_once $baseDir . 'Traits/PinUserTrait.php';
    require_once $baseDir . 'Traits/CelTrait.php';
    require_once $baseDir . 'Tarifador.class.php';

    class TarifadorTest extends TestCase {

        protected static $faker;
        protected $tarifador;

        public static function setUpBeforeClass(): void {
            self::$faker = Faker\Factory::create('pt_BR');
        }

        public function setUp(): void {

            $mockDatabase = $this->getMockBuilder(Database::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

            $mockBMO = $this->getMockBuilder(\stdClass::class)
                            ->addMethods(['Database']) 
                            ->getMock();

            $mockBMO->Database = $mockDatabase;

            $this->tarifador = new Tarifador($mockBMO);
        }

        protected function invokeMethod(&$object, $methodName, array $parameters = [])
        {
            $reflection = new \ReflectionClass(get_class($object));
            $method = $reflection->getMethod($methodName);
            $method->setAccessible(true);
            return $method->invokeArgs($object, $parameters);
        }

        public function testCallClassification() {
            $mockTrunkList = ['pjsip/vivo', 'khomp/', 'sip/vono'];

            // Cenário A: OUTBOUND
            $cdrOut = ['channel' => 'PJSIP/2000-01', 'dstchannel' => 'PJSIP/VIVO-02'];
            $type = $this->invokeMethod($this->tarifador, 'getCallType', [$cdrOut, $mockTrunkList]);
            $this->assertEquals('OUTBOUND', $type, 'Erro SAÍDA PJSIP');

            // Cenário B: INBOUND
            $cdrIn = ['channel' => 'Khomp/B0L0/999', 'dstchannel' => 'PJSIP/2000-03'];
            $type = $this->invokeMethod($this->tarifador, 'getCallType', [$cdrIn, $mockTrunkList]);
            $this->assertEquals('INBOUND', $type, 'Erro ENTRADA Khomp');

            // Cenário C: INTERNAL
            $cdrInt = ['channel' => 'PJSIP/1001-04', 'dstchannel' => 'PJSIP/1002-05'];
            $type = $this->invokeMethod($this->tarifador, 'getCallType', [$cdrInt, $mockTrunkList]);
            $this->assertEquals('INTERNAL', $type, 'Erro INTERNA');
        }

        public function testCostCalculation() {
            $rates = [['name' => 'Local', 'dial_pattern' => '9XXXXXXXX', 'rate' => '1.00']];

            $this->assertNull(
                $this->invokeMethod($this->tarifador, 'cost', ['999998888', 3, $rates]),
                'Erro < 3s'
            );

            $res = $this->invokeMethod($this->tarifador, 'cost', ['999998888', 20, $rates]);
            $this->assertEquals('0.50', $res['cost'], 'Erro Mínimo 30s');

            $res = $this->invokeMethod($this->tarifador, 'cost', ['999998888', 45, $rates]);
            $this->assertEquals('0.80', $res['cost'], 'Erro Fração 6s');
        }

        public function testAsteriskMatch() {
            $pattern = '_9[6-9]XXXXXXX';
            $this->assertTrue($this->invokeMethod($this->tarifador, 'match', [$pattern, '999998888']));
            $this->assertFalse($this->invokeMethod($this->tarifador, 'match', [$pattern, '33334444']));
        }

        public function testStressClassification() {
            $mockTrunkList = ['pjsip/trunk_a', 'khomp/'];
            
            for ($i = 0; $i < 1000; $i++) {
                $isSourceTrunk = self::$faker->boolean(30);
                $isDestTrunk = self::$faker->boolean(30);
                
                $chan = $isSourceTrunk ? 'PJSIP/trunk_a-123' : 'PJSIP/1000-123';
                $dst = $isDestTrunk ? 'Khomp/B0L0/123' : 'PJSIP/2000-123';

                $type = $this->invokeMethod($this->tarifador, 'getCallType', [['channel'=>$chan, 'dstchannel'=>$dst], $mockTrunkList]);

                if ($isSourceTrunk) $this->assertEquals('INBOUND', $type);
                elseif ($isDestTrunk) $this->assertEquals('OUTBOUND', $type);
                else $this->assertEquals('INTERNAL', $type);
            }
        }
    }
}