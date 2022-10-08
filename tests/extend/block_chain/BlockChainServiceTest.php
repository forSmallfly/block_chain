<?php

use block_chain\BlockChainService;
use \block_chain\BlockChainTool;
use PHPUnit\Framework\TestCase;
use think\App;

class BlockChainServiceTest extends TestCase
{
    /**
     * @var BlockChainService
     */
    private $blockChainService;

    public function setUp(): void
    {
        (new App())->http->run();
        $this->blockChainService = new BlockChainService();
    }

    /**
     * 获取最新区块号
     *
     * @return mixed
     * @throws Exception
     */
    public function testGetBlockNumber()
    {
        $blockNumber = $this->blockChainService->getBlockNumber();
        $this->assertIsString($blockNumber);
        // var_dump($blockNumber);

        return BlockChainTool::hexToNumber($blockNumber);
    }

    /**
     * 根据区块号获取区块信息
     *
     * @depends testGetBlockNumber
     *
     * @param int $blockNumber
     * @return mixed
     * @throws Exception
     */
    public function testGetBlockInfo(int $blockNumber)
    {
        $blockInfo = $this->blockChainService->getBlockInfo($blockNumber);
        $this->assertNotEmpty($blockInfo);
        // var_export($blockInfo);

        return $blockInfo;
    }

    /**
     * 通过交易hash获取交易回执
     *
     * @depends testGetBlockInfo
     *
     * @param array $blockInfo
     * @throws Exception
     */
    public function testGetTransactionReceipt(array $blockInfo)
    {
        $this->assertNotEmpty($blockInfo['transactions']);

        $transactionReceipt = $this->blockChainService->getTransactionReceipt($blockInfo['transactions'][0]['hash']);
        $this->assertNotEmpty($transactionReceipt);
        var_export($transactionReceipt);
    }

    /**
     * 通过交易hash获取交易信息
     *
     * @depends testGetBlockInfo
     *
     * @param array $blockInfo
     * @throws Exception
     */
    public function testGetTransactionByHash(array $blockInfo)
    {
        $this->assertNotEmpty($blockInfo['transactions']);

        $transactionInfo = $this->blockChainService->getTransactionByHash($blockInfo['transactions'][0]['hash']);
        $this->assertNotEmpty($transactionInfo);
        var_export($transactionInfo);
    }

    /**
     * 获取当前gas价格（单位 wei）
     *
     * @return mixed
     * @throws Exception
     */
    public function testGetGasPrice()
    {
        $gasPrice = $this->blockChainService->getGasPrice();
        $this->assertIsString($gasPrice);
        var_export($gasPrice);

        return $gasPrice;
    }

    /**
     * 获取地址交易系列号，类似交易ID (nonce)
     *
     * @return mixed
     * @throws Exception
     */
    public function testGetAddressTransactionNonce()
    {
        $address = '0xac05d75850dFEd2D94A940fCB60B038818Ad9a7E';
        $nonce   = $this->blockChainService->getAddressTransactionNonce($address);
        $this->assertIsString($nonce);
        var_export($nonce);

        return $nonce;
    }

    /**
     * 估计发送交易需要消耗的Gas
     *
     * @depends testGetGasPrice
     * @depends testGetAddressTransactionNonce
     *
     * @param string $nonce
     * @param string $gasPrice
     * @return array
     * @throws Exception
     */
    public function testEstimateGas(string $nonce, string $gasPrice)
    {
        $method       = 'approve';
        $params       = ['0x7A7F80d6c08fdC4619a3F31B778DFe7CDA0124ca', 103];
        $chainId      = config('block_chain.chain_id');
        $txObjChainId = BlockChainTool::numberToHex($chainId);
        $fromAddress  = '0x8Be6fCbE5A7297933da0D6B52F0f257Ac5CDD00F';
        $toAddress    = '0x9450Ba713Fd6332D87A5ff7098aB7e14BfB310b0';
        $value        = '0x0';
        $data         = $this->blockChainService->makeCustomByteCode($method, $params);
        $txObj        = [
            'chainId'  => $txObjChainId,
            'nonce'    => $nonce,
            'from'     => $fromAddress,
            'to'       => $toAddress,
            'value'    => $value,
            'gasPrice' => $gasPrice,
            'data'     => $data,
        ];

        $gas = $this->blockChainService->estimateGas($txObj);
        $this->assertIsString($gas);
        $gasRatio = config('block_chain.gas_ratio');
        if (!empty($gasRatio) && is_numeric($gasRatio)) {
            $gas = BlockChainTool::numberToHex(bcmul((string)BlockChainTool::hexToWei($gas), (string)$gasRatio, 0));
            var_export($gas);
        }

        $txObj['gas']     = $gas;
        $txObj['chainId'] = $chainId;

        return $txObj;
    }

    /**
     * 发送带签名数据的交易
     *
     * @depends testEstimateGas
     *
     * @param array $txObj
     * @throws Exception
     */
    public function testSendSignTransaction(array $txObj)
    {
        $fromAddressKey  = 'fe804a4d2f856d8abb98271d1a067f408b8a0b2b202d0d872d786aa507a5c112';
        $transactionHash = $this->blockChainService->sendSignTransaction($txObj, $fromAddressKey);
        $this->assertIsString($transactionHash);
        var_export($transactionHash);
    }

    /**
     * 调用合约方法
     *
     * @throws Exception
     */
    public function testCall()
    {
        $method = 'tokenOfOwnerByIndex';
        $params = ['0x8Be6fCbE5A7297933da0D6B52F0f257Ac5CDD00F', 0];

        $toAddress = '0x9450Ba713Fd6332D87A5ff7098aB7e14BfB310b0';
        $data      = $this->blockChainService->makeCustomByteCode($method, $params);
        $txObj     = [
            'to'   => $toAddress,
            'data' => $data,
        ];

        $outputData = $this->blockChainService->call($txObj);
        $this->assertIsString($outputData);

        $result = $this->blockChainService->decodeCustomByteCode($method, $outputData);
        var_export($result);
    }

    /**
     * 获取合约日志
     *
     * @throws Exception
     */
    public function testGetPastEvents()
    {
        $fromBlock = 17368723;
        $toBlock   = 17370723;
        $topics    = [
            '0x9bdf8910805ef9e726cec540ba9635615791a9b52820eedf27f127381334754c',
            '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef'
        ];
        $address   = [
            '0x3eec0b75be6ac4c0c5318749f3f60907edffe3fb',
            '0x4181e255c5324f4eeeddbf7acaa1fe962b81a2df'
        ];

        $result = $this->blockChainService->getPastEvents($fromBlock, $toBlock, $topics, $address);
        $this->assertIsArray($result);
        var_export($result);
    }
}