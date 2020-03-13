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

class TestORMQueryBuilderCommand extends Command
{
    private $entityManager;
    private $opentracing;

    public function __construct(EntityManager $entityManager, Opentracing $opentracing)
    {
        parent::__construct('test:doctrine:orm-query-builder');

        $this->entityManager = $entityManager;
        $this->opentracing = $opentracing;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityRepository = $this->entityManager->getRepository(TestEntity::class);
        $selectQB = $entityRepository->createQueryBuilder('e');
        $selectQB->select('COUNT(e.id)')
            ->where($selectQB->expr()->isNotNull('e.str'));
        Assert::eq($selectQB->getQuery()->getSingleScalarResult(), 0);
        Assert::eq($entityRepository->count([]), 0);

        $first = new TestEntity();
        $first->setStr('a');
        $this->entityManager->persist($first);
        $this->entityManager->flush();
        Assert::eq($selectQB->getQuery()->getSingleScalarResult(), 1);
        Assert::eq($entityRepository->count([]), 1);

        $second = new TestEntity();
        $second->setStr('b');
        $this->entityManager->persist($second);
        $this->entityManager->flush();
        Assert::eq($selectQB->getQuery()->getSingleScalarResult(), 2);
        Assert::eq($entityRepository->count([]), 2);

        $this->entityManager->createQuery('UPDATE App\Entity\TestEntity e SET e.str = NULL WHERE e.str = :value')->execute(['value' => 'a']);
        Assert::eq($selectQB->getQuery()->getSingleScalarResult(), 1);
        Assert::eq($entityRepository->count([]), 2);

        $deleteQB = $this->entityManager->createQueryBuilder();
        $deleteQB->delete('App:TestEntity', 'e')
            ->where($deleteQB->expr()->eq('e.id', $second->getId()));
        $deleteQB->getQuery()->execute();
        $this->entityManager->flush();
        Assert::eq($selectQB->getQuery()->getSingleScalarResult(), 0);
        Assert::eq($entityRepository->count([]), 1);

        $carrier = [];
        $this->opentracing->getTracerInstance()->inject($this->opentracing->getTracerInstance()->getActiveSpan()->getContext(), TEXT_MAP, $carrier);
        $output->writeln(current($carrier));

        return 0;
    }
}
