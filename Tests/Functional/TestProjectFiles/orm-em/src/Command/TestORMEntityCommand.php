<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\TestEntity;
use Auxmoney\OpentracingBundle\Internal\Opentracing;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;
use const OpenTracing\Formats\TEXT_MAP;

class TestORMEntityCommand extends Command
{
    private $entityManager;
    private $opentracing;

    public function __construct(EntityManager $entityManager, Opentracing $opentracing)
    {
        parent::__construct('test:doctrine:orm-entity');

        $this->entityManager = $entityManager;
        $this->opentracing = $opentracing;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $selectQuery = $this->entityManager->createQuery('SELECT COUNT(e.id) FROM App\Entity\TestEntity e WHERE e.str IS NOT NULL');

        Assert::eq($selectQuery->getSingleScalarResult(), 0);

        $first = new TestEntity();
        $first->setStr('a');
        $this->entityManager->persist($first);
        $this->entityManager->flush();
        Assert::eq($selectQuery->getSingleScalarResult(), 1);

        $second = new TestEntity();
        $second->setStr('b');
        $this->entityManager->persist($second);
        $this->entityManager->flush();
        Assert::eq($selectQuery->getSingleScalarResult(), 2);

        $first->setStr(null);
        $this->entityManager->flush();
        Assert::eq($selectQuery->getSingleScalarResult(), 1);

        $this->entityManager->remove($second);
        $this->entityManager->flush();
        Assert::eq($selectQuery->getSingleScalarResult(), 0);

        $carrier = [];
        $this->opentracing->getTracerInstance()->inject($this->opentracing->getTracerInstance()->getActiveSpan()->getContext(), TEXT_MAP, $carrier);
        $output->writeln(current($carrier));

        return 0;
    }
}
