<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Providers\Expression\IExpressionProvider;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;

/**
 * Class ExpressionProcessor.
 *
 * Class to process an expression tree and generate specialized
 * (e.g. PHP) expression using expression provider
 */
class ExpressionProcessor
{
    private $expressionProvider;

    /**
     * Construct new instance of ExpressionProcessor.
     *
     * @param IExpressionProvider $expressionProvider Reference to the language specific provider
     */
    public function __construct(IExpressionProvider $expressionProvider)
    {
        $this->expressionProvider = $expressionProvider;
    }

    /**
     * Process the expression tree using expression provider and return the
     * expression as string.
     *
     * @param AbstractExpression $rootExpression The root of the expression tree
     *
     * @return string
     */
    public function processExpression(AbstractExpression $rootExpression)
    {
        return $this->_processExpressionNode($rootExpression);
    }

    /**
     * Recursive function to process each node of the expression.
     *
     * @param AbstractExpression $expression Current node to process
     *
     * @return string|null The language specific expression
     */
    private function _processExpressionNode(AbstractExpression $expression = null)
    {
        $funcName = null;
        if ($expression instanceof ArithmeticExpression) {
            $funcName = 'onArithmeticExpression';
        } elseif ($expression instanceof LogicalExpression) {
            $funcName = 'onLogicalExpression';
        } elseif ($expression instanceof RelationalExpression) {
            $funcName = 'onRelationalExpression';
        }

        if (null !== $funcName) {
            $left = $this->_processExpressionNode($expression->getLeft());
            $right = $this->_processExpressionNode($expression->getRight());

            return $this->expressionProvider->$funcName(
                $expression->getNodeType(),
                $left,
                $right
            );
        }

        if ($expression instanceof ConstantExpression) {
            return $this->expressionProvider->onConstantExpression(
                $expression->getType(),
                $expression->getValue()
            );
        }

        if ($expression instanceof PropertyAccessExpression) {
            return $this->expressionProvider->onPropertyAccessExpression(
                $expression
            );
        }

        if ($expression instanceof FunctionCallExpression) {
            $params = [];
            foreach ($expression->getParamExpressions() as $paramExpression) {
                $params[] = $this->_processExpressionNode($paramExpression);
            }

            return $this->expressionProvider->onFunctionCallExpression(
                $expression->getFunctionDescription(),
                $params
            );
        }

        if ($expression instanceof UnaryExpression) {
            $child = $this->_processExpressionNode($expression->getChild());

            return $this->expressionProvider->onUnaryExpression(
                $expression->getNodeType(),
                $child
            );
        }

        return null;
    }
}
