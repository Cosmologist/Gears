<?php

namespace Cosmologist\Gears\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\DBAL\Query\QueryBuilder;
use Override;
use RuntimeException;

/**
 * Apply Doctrine Collection expressions (Criteria) to the DBAL QueryBuilder
 *
 * The converter implementation incomplete - a limited number of {@link Comparison} currently supported.
 *
 * ```
 * $criteria = new Criteria();
 * $criteria->where(new Expr\Comparison('uuid', Expr\Comparison::EQ, $order->getValue()));
 * $criteria->orWhere(new Expr\Comparison('_related->"$[*]"', Expr\Comparison::MEMBER_OF, $order->getValue()));
 *
 * $queryBuilder = $this->connection->createQueryBuilder();
 * $visitor      = new DbalExpressionVisitor($queryBuilder);
 *
 * $queryBuilder
 *     ->select('*')
 *     ->from($this->tableName)
 *     ->andWhere($criteria->getWhereExpression()->visit($visitor))
 *     ->executeQuery();
 * ```
 *
 * @see \Doctrine\ORM\Persisters\SqlExpressionVisitor
 */
class DbalExpressionVisitor extends ExpressionVisitor
{
    public function __construct(private readonly QueryBuilder $queryBuilder)
    {
    }

    #[Override]
    public function walkComparison(Comparison $comparison): string
    {
        if ($comparison->getOperator() === Comparison::EQ) {
            return $this->queryBuilder->expr()->eq($comparison->getField(), $this->dispatch($comparison->getValue()));
        }
        if ($comparison->getOperator() === Comparison::MEMBER_OF) {
            return $this->dispatch($comparison->getValue()) . ' MEMBER OF(' . $comparison->getField() . ')';
        }

        throw new RuntimeException('Unknown comparison operator ' . $comparison->getOperator());
    }

    #[Override]
    public function walkValue(Value $value): string
    {
        // Bind value as param
        $params = $this->queryBuilder->getParameters();
        $this->queryBuilder->setParameter(count($params), $value->getValue());

        // Return placeholder as value
        return '?';
    }

    #[Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        return match ($expr->getType()) {
            CompositeExpression::TYPE_AND => '(' . implode(' AND ', $expressionList) . ')',
            CompositeExpression::TYPE_OR => '(' . implode(' OR ', $expressionList) . ')',
            CompositeExpression::TYPE_NOT => 'NOT (' . $expressionList[0] . ')',
            default => throw new RuntimeException('Unknown composite ' . $expr->getType()),
        };
    }
}
