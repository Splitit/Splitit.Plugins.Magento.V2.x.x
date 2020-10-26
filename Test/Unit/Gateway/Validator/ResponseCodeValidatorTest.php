<?php

namespace Splitit\PaymentGateway\Test\Unit\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Splitit\PaymentGateway\Gateway\Http\Client\SplititCreateApiImplementation;
use Splitit\PaymentGateway\Gateway\Validator\ResponseCodeValidator;

class ResponseCodeValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResultInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    public function setUp()
    {
        $this->resultFactory = $this->getMockBuilder(
            'Magento\Payment\Gateway\Validator\ResultInterfaceFactory'
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMock(ResultInterface::class);
    }

    /**
     * @param array $response
     * @param array $expectationToResultCreation
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $response, array $expectationToResultCreation)
    {
        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(
                $expectationToResultCreation
            )
            ->willReturn($this->resultMock);

        $validator = new ResponseCodeValidator($this->resultFactory);

        static::assertInstanceOf(
            ResultInterface::class,
            $validator->validate(['response' => $response])
        );
    }

    public function validateDataProvider()
    {
        return [
            'fail_1' => [
                'response' => [],
                'expectationToResultCreation' => [
                    'isValid' => false,
                    'failsDescription' => [__('Gateway rejected the transaction.')]
                ]
            ],
            'fail_2' => [
                'response' => [ResponseCodeValidator::RESULT_CODE => SplititCreateApiImplementation::FAILURE],
                'expectationToResultCreation' => [
                    'isValid' => false,
                    'failsDescription' => [__('Gateway rejected the transaction.')]
                ]
            ],
            'success' => [
                'response' => [ResponseCodeValidator::RESULT_CODE => SplititCreateApiImplementation::SUCCESS],
                'expectationToResultCreation' => [
                    'isValid' => true,
                    'failsDescription' => []
                ]
            ]
        ];
    }
}
