<?php

namespace block_chain;

use PHPUnit\Framework\TestCase;
use think\App;

class BlockChainToolTest extends TestCase
{
    public function setUp(): void
    {
        (new App())->http->run();
    }

    /**
     * 数值转换成hex值
     */
    public function testNumberToHex()
    {
        $number = 256;
        $hex    = BlockChainTool::numberToHex($number);
        $this->assertEquals('0x100', $hex);
    }

    /**
     * hex值转换成数值
     */
    public function testHexToNumber()
    {
        $hex    = '0x100';
        $number = BlockChainTool::hexToNumber($hex);
        $this->assertEquals(256, $number);
    }

    /**
     * hex值转换成ether（单位：以太）
     */
    public function testHexToEther()
    {
        $hex   = '0x16345785d8a0000';
        $ether = BlockChainTool::hexToEther($hex);
        $this->assertEquals(0.1, $ether);
    }

    /**
     * hex值转换成Wei
     */
    public function testHexToWei()
    {
        $hex = '0x16345785d8a0000';
        $wei = BlockChainTool::hexToWei($hex);
        $this->assertEquals(100000000000000000, $wei);
    }

    /**
     * ether转换成wei（单位：以太）
     */
    public function testEtherToWei()
    {
        $ether = 0.1;
        $wei   = BlockChainTool::etherToWei($ether);
        $this->assertEquals(100000000000000000, $wei);
    }

    /**
     * wei值转换成ether（单位：以太）
     */
    public function testWeiToEther()
    {
        $wei   = 100000000000000000;
        $ether = BlockChainTool::weiToEther($wei);
        $this->assertEquals(0.1, $ether);
    }

    /**
     * 格式化地址
     */
    public function testFormatAddress()
    {
        $address = '0x000000000000000000000000ac05d75850dfed2d94a940fcb60b038818ad9a7e';
        $address = BlockChainTool::formatAddress($address);
        $this->assertEquals('0xac05d75850dfed2d94a940fcb60b038818ad9a7e', $address);
    }
}