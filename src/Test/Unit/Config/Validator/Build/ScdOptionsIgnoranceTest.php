<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Build\ScdOptionsIgnorance;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Build\Reader as BuildReader;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ScdOptionsIgnoranceTest extends TestCase
{
    /**
     * @var ScdOptionsIgnorance
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReader;

    /**
     * @var BuildReader|MockObject
     */
    private $buildReader;

    /**
     * @var ScdOnBuild|MockObject
     */
    private $scdOnBuildValidator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->environmentReader = $this->createMock(EnvironmentReader::class);
        $this->buildReader = $this->createMock(BuildReader::class);
        $this->scdOnBuildValidator = $this->createMock(ScdOnBuild::class);

        $this->validator = new ScdOptionsIgnorance(
            $this->resultFactoryMock,
            $this->environmentReader,
            $this->buildReader,
            $this->scdOnBuildValidator
        );
    }

    public function testValidateScdOnBuild()
    {
        $this->scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Success::class));
        $this->buildReader->expects($this->never())
            ->method('read');
        $this->environmentReader->expects($this->never())
            ->method('read');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateScdNotOnBuild()
    {
        $this->scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Error::class));
        $this->buildReader->expects($this->exactly(3))
            ->method('read')
            ->willReturn([
                StageConfigInterface::VAR_SCD_STRATEGY => 'quick'
            ]);
        $this->environmentReader->expects($this->exactly(2))
            ->method('read')
            ->willReturn([]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Next variables are ignored: SCD_STRATEGY.'));

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    public function testValidateScdNotOnBuildWithEnvironmentConfig()
    {
        $this->scdOnBuildValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Error::class));
        $this->buildReader->expects($this->exactly(3))
            ->method('read')
            ->willReturn([
                StageConfigInterface::VAR_SCD_STRATEGY => 'quick'
            ]);
        $this->environmentReader->expects($this->exactly(2))
            ->method('read')
            ->willReturn([
                StageConfigInterface::SECTION_STAGE => [
                    StageConfigInterface::STAGE_BUILD => [
                        StageConfigInterface::VAR_SCD_THREADS => 3
                    ]
                ]
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Next variables are ignored: SCD_STRATEGY, SCD_THREADS.'));

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
