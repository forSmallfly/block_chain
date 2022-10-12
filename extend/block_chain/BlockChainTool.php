<?php

namespace block_chain;

use phpseclib\Math\BigInteger;
use Web3\Contracts\Ethabi;
use Web3\Contracts\Types\Address;
use Web3\Contracts\Types\Boolean;
use Web3\Contracts\Types\Bytes;
use Web3\Contracts\Types\DynamicBytes;
use Web3\Contracts\Types\Integer;
use Web3\Contracts\Types\Str;
use Web3\Contracts\Types\Uinteger;
use Web3\Utils;

/**
 * 区块链工具类
 * @package block_chain
 */
class BlockChainTool
{
    // web3.php Eth abi对象
    private $ethAbi;

    /**
     * BlockChainTool constructor.
     */
    public function __construct()
    {
        $this->ethAbi = new Ethabi([
            'address'      => new Address,
            'bool'         => new Boolean,
            'bytes'        => new Bytes,
            'dynamicBytes' => new DynamicBytes,
            'int'          => new Integer,
            'string'       => new Str,
            'uint'         => new Uinteger
        ]);
    }

    /**
     * 数值转换成hex值
     *
     * @param int|string|BigInteger $value
     * @return string
     */
    public static function numberToHex($value)
    {
        return $value == 0 ? '0x0' : Utils::toHex($value, true);
    }

    /**
     * hex值转换成数值
     *
     * @param string $hex
     * @return int
     */
    public static function hexToNumber(string $hex)
    {
        $bn = Utils::toBn($hex);

        return intval($bn->toString());
    }

    /**
     * hex值转换成ether（单位：以太）
     *
     * @param string $hex
     * @return string
     */
    public static function hexToEther(string $hex)
    {
        list($bnq, $bnr) = Utils::fromWei($hex, 'ether');

        $ether = bcpow('10', '18', 0); // 1000000000000000000

        // 整数部分
        $int = $bnq->toString();
        $int = self::rtrimZero($int);

        // 小数部分
        $float = $bnr->toString();
        $float = bcdiv($float, $ether, 18);
        $float = self::rtrimZero($float);

        $result = bcadd($int, $float, 18);
        $result = self::rtrimZero($result);
        return $result;
    }

    /**
     * hex值转换成Wei
     *
     * @param string $hex
     * @return float
     */
    public static function hexToWei(string $hex)
    {
        $wei = Utils::toWei($hex, 'wei');

        return floatval($wei->toString());
    }

    /**
     * ether转换成wei（单位：以太）
     *
     * @param string $ether
     * @return string
     */
    public static function etherToWei(string $ether)
    {
        $wei = Utils::toWei($ether, 'ether');

        return $wei->toString();
    }

    /**
     * wei值转换成ether（单位：以太）
     *
     * @param string $wei
     * @return string
     */
    public static function weiToEther(string $wei)
    {
        list($bnq, $bnr) = Utils::fromWei($wei, 'ether');

        $int = $bnq->toString();
        $int = self::rtrimZero($int);

        $float = $bnr->toString();
        $ether = bcpow('10', '18', 0);
        $float = bcdiv($float, $ether, 18);

        $ether = bcadd($int, $float, 18);
        return self::rtrimZero($ether);
    }

    /**
     * 去掉字符串末尾的0
     *
     * @param string $string
     * @return string
     */
    public static function rtrimZero(string $string)
    {
        $pos = strpos($string, '.');
        if ($pos !== false) {
            $string = rtrim($string, '0');
            if (substr($string, -1) == '.') {
                $string = rtrim($string, '.');
            }
        }
        return $string;
    }

    /**
     * 格式化地址
     *
     * @param string $str
     * @return false|string|string[]|null
     */
    public static function formatAddress(string $str)
    {
        $address = '0x' . mb_substr(Utils::stripZero($str), 24, 40);
        return mb_strtolower($address);
    }
}