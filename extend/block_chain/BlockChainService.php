<?php
namespace block_chain;

use Exception;
use GuzzleHttp\Client;
use Web3\Contracts\Ethabi;
use Web3\Contracts\Types\Address;
use Web3\Contracts\Types\Boolean;
use Web3\Contracts\Types\Bytes;
use Web3\Contracts\Types\DynamicBytes;
use Web3\Contracts\Types\Integer;
use Web3\Contracts\Types\Str;
use Web3\Contracts\Types\Uinteger;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;

/**
 * 区块链服务类
 * @package block_chain
 */
class BlockChainService
{
    // 当前区块链标识
    private $chainId;

    // 当前区块链rpc
    private $rpc;

    // web3.php Eth abi对象
    private $ethAbi;

    // 合约方法
    private $contractFunctions = [];

    // 默认的区块参数
    private $defaultBlock = ["earliest", "latest", "pending"];

    /**
     * BlockChainService constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $chainId   = config('block_chain.chain_id');
        $chainList = config('block_chain.chain_list', []);

        if (empty($chainId)) {
            throw new Exception('chainId 未设置');
        }

        if (empty($chainList[$chainId])) {
            throw new Exception('chainId 不可用');
        }

        $this->chainId = $chainId;
        $this->rpc     = $chainList[$chainId]['rpc'];

        $contractFunctions = config('contract_functions', []);
        foreach ($contractFunctions as $contractName => $functions) {
            $this->contractFunctions = array_merge($this->contractFunctions, $functions);
        }

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
     * 获取最新区块号
     *
     * @return mixed
     * @throws Exception
     */
    public function getBlockNumber()
    {
        $method = "eth_blockNumber";
        $params = [];
        $result = $this->postRequest($method, $params, 83);
        return self::parseResult($result);
    }

    /**
     * 根据区块号获取区块信息
     *
     * @param int|string $blockId
     * @param bool       $full
     * @return mixed
     * @throws Exception
     */
    public function getBlockInfo($blockId, $full = true)
    {
        if (in_array($blockId, $this->defaultBlock)) {
            $method = "eth_getBlockByNumber";
        } else if (is_numeric($blockId)) {
            $method  = "eth_getBlockByNumber";
            $blockId = Utils::toHex($blockId, true);
        } else {
            $method = "eth_getBlockByHash";
        }
        $params = [$blockId, $full];
        $result = $this->postRequest($method, $params, 1);
        return self::parseResult($result);
    }

    /**
     * 通过交易hash获取交易回执
     *
     * @param string $txHash
     * @return mixed
     * @throws Exception
     */
    public function getTransactionReceipt(string $txHash)
    {
        $method = "eth_getTransactionReceipt";
        $params = [$txHash];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * 通过交易hash获取交易信息
     *
     * @param string $txHash
     * @return mixed
     * @throws Exception
     */
    public function getTransactionByHash(string $txHash)
    {
        $method = "eth_getTransactionByHash";
        $params = [$txHash];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * 获取当前gas价格（单位 wei）
     *
     * @return mixed
     * @throws Exception
     */
    public function getGasPrice()
    {
        $method = "eth_gasPrice";
        $params = [];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * 获取地址交易系列号，类似交易ID (nonce)
     *
     * @param string $address
     * @param string $quantity
     * @return mixed
     * @throws Exception
     */
    public function getAddressTransactionNonce(string $address, string $quantity = 'latest')
    {
        $method = "eth_getTransactionCount";
        $params = [$address, $quantity];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * 估计发送交易需要消耗的Gas
     *
     * @param array $txObj
     * @return mixed
     * @throws Exception
     */
    public function estimateGas(array $txObj)
    {
        $method = "eth_estimateGas";
        $params = [$txObj];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * 发送带签名数据的交易
     *
     * @param array  $txObj
     * @param string $fromAddressKey
     * @return mixed
     * @throws Exception
     */
    public function sendSignTransaction(array $txObj, string $fromAddressKey)
    {
        // 生成带签名的交易数据
        $transaction       = new Transaction($txObj);
        $signedTransaction = $transaction->sign($fromAddressKey);
        $signedTransaction = '0x' . $signedTransaction;

        // 发送交易（签名数据）
        return $this->sendRawTransaction($signedTransaction);
    }

    /**
     * 发送交易（签名数据）
     *
     * @param string $txObj
     * @return mixed
     * @throws Exception
     */
    private function sendRawTransaction(string $txObj)
    {
        $method = "eth_sendRawTransaction";
        $params = [$txObj];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * 调用合约方法
     *
     * @param array  $txObj
     * @param string $quantity
     * @return mixed
     * @throws Exception
     */
    public function call(array $txObj, $quantity = "latest")
    {
        $method = "eth_call";
        $params = [$txObj, $quantity];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * 获取合约日志
     *
     * @param int   $fromBlock
     * @param int   $toBlock
     * @param array $topics
     * @param array $address
     * @return mixed
     * @throws Exception
     */
    public function getPastEvents(int $fromBlock, int $toBlock, array $topics, array $address)
    {
        $method = "eth_getLogs";
        $params = [
            [
                "fromBlock" => BlockChainTool::numberToHex($fromBlock),
                "toBlock"   => BlockChainTool::numberToHex($toBlock),
                "topics"    => [$topics],
                "address"   => $address
            ]
        ];
        $result = $this->postRequest($method, $params);
        return self::parseResult($result);
    }

    /**
     * post请求
     *
     * @param string $method
     * @param array  $params
     * @param int    $id
     * @return mixed
     * @throws Exception
     */
    private function postRequest(string $method, array $params, int $id = 67)
    {
        try {
            $data = [
                "jsonrpc" => "2.0",
                "method"  => $method,
                "params"  => $params,
                "id"      => $id
            ];

            $client   = new Client();
            $response = $client->post($this->rpc, [
                'body'            => json_encode($data, JSON_UNESCAPED_UNICODE),
                'verify'          => false,
                'timeout'         => 1,
                'connect_timeout' => 1,
                'headers'         => [
                    'content-type' => 'application/json'
                ]
            ]);

            $result = $response->getBody()->getContents();
            return json_decode($result, true);
        } catch (\Throwable $throwable) {
            throw new Exception($throwable->getMessage());
        }
    }

    /**
     * 解析结果
     *
     * @param array $result
     * @return mixed
     * @throws Exception
     */
    private static function parseResult(array $result)
    {
        if (empty($result)) {
            throw new Exception("获取信息为空");
        } else if (isset($result['error'])) {
            throw new Exception(json_encode($result['error'], JSON_UNESCAPED_UNICODE));
        } else {
            return $result['result'];
        }
    }

    /**
     * 生成自定义合约方法的智能合约调用Byte code（即data）
     *
     * @param string $functionName
     * @param array  $params
     * @return string
     */
    public function makeCustomByteCode(string $functionName, array $params)
    {
        $functionInfo = $this->contractFunctions[$functionName];
        $methodSign   = $this->ethAbi->encodeFunctionSignature($functionInfo['method']);
        $paramsEncode = $this->ethAbi->encodeParameters($functionInfo['params'], $params);
        $paramsEncode = Utils::stripZero($paramsEncode);
        return $methodSign . $paramsEncode;
    }

    /**
     * 解码调用的智能合约方法和参数
     *
     * @param string $input
     * @return array
     */
    public function decodeContractFunction(string $input)
    {
        $result           = [];
        $inputMethod      = substr($input, 0, 8);
        $function         = $this->getContractFunction($inputMethod);
        $result['method'] = $function['method'];
        $result['params'] = $function['params'];
        $inputParams      = substr($input, 8);

        if ($function['params']) {
            $result['params'] = $this->ethAbi->decodeParameters($function['params'], $inputParams);
            foreach ($function['params'] as $key => $solidityType) {
                switch ($solidityType) {
                    case 'uint256':
                        $result['params'][$key] = $result['params'][$key]->toString();
                        break;
                    case 'uint256[]':
                        $result['params'][$key] = array_map(function ($item) {
                            return $item->toString();
                        }, $result['params'][$key]);
                        break;
                    case 'string[]':
                        $paramsTmp = Utils::stripZero($input);
                        $paramsTmp = str_split($paramsTmp, 64);
                        $length    = '-' . bcmul(count($result['params'][$key]), 2, 0);
                        $paramsTmp = array_slice($paramsTmp, $length);
                        $tmp       = 1;
                        foreach ($result['params'][$key] as $k => $v) {
                            $codeTmp                    = '0x000000000000000000000000000000000000000000000000000000000000002';
                            $tmpRes                     = bcsub(bcpow(2, $tmp, 0), 1, 0);
                            $codeTmp                    = $codeTmp . '0' . $paramsTmp[bcsub($tmpRes, 1, 0)] . $paramsTmp[$tmpRes];
                            $codeDecode                 = $this->ethAbi->decodeParameters(['string'], $codeTmp);
                            $result['params'][$key][$k] = $codeDecode[0] ?? '';
                            $tmp++;
                        }
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * 验证方法签名，返回智能合约方法
     *
     * @param string $method
     * @return array|mixed
     */
    private function getContractFunction(string $method)
    {
        foreach ($this->contractFunctions as $functionName => $functionInfo) {
            $methodSign = $this->ethAbi->encodeFunctionSignature($functionInfo['method']);
            $methodSign = substr($methodSign, 2);
            if ($methodSign == $method) {
                return $functionInfo;
            }
        }
        return ['method' => '没有找到所调用的智能合约方法', 'params' => []];
    }

    /**
     * 解析调用智能合约返回的结果（即outputs数据）
     *
     * @param string $functionName
     * @param string $params
     * @return array|string
     */
    public function decodeCustomByteCode(string $functionName, string $params)
    {
        $function = $this->contractFunctions[$functionName];

        $result = $this->ethAbi->decodeParameters($function['outputs'], $params);
        foreach ($function['outputs'] as $key => $solidityType) {
            switch ($solidityType) {
                case 'uint256':
                    $result[$key] = $result[$key]->toString();
                    break;
                case 'uint256[]':
                    $result[$key] = array_map(function ($item) {
                        return $item->toString();
                    }, $result[$key]);
                    break;
            }
        }

        return $result;
    }
}