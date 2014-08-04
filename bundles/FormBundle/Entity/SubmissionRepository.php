<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Entity;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * IpAddressRepository
 */
class SubmissionRepository extends CommonRepository
{

    public function getEntities($args = array())
    {
        $q = $this->createQueryBuilder('s')
            ->select('s, r, i')
            ->leftJoin('s.results', 'r')
            ->leftJoin('r.field', 'f')
            ->leftJoin('s.ipAddress', 'i');

        $this->buildClauses($q, $args);

        $q->addOrderBy('f.order', 'ASC');
        $query = $q->getQuery();
        $query->setHydrationMode(Query::HYDRATE_ARRAY);
        $results = new Paginator($query);
        return $results;
    }

    protected function getFilterExpr(&$q, $filter)
    {
        if ($filter['column'] == 's.dateSubmitted') {
            $date  = $this->factory->getDate($filter['value'], 'Y-m-d')->toUtcString();
            $date1 = $this->generateRandomParameterName();
            $date2 = $this->generateRandomParameterName();
            $parameters = array($date1 => $date . ' 00:00:00', $date2 => $date . ' 23:59:59');
            $expr = $q->expr()->andX(
                $q->expr()->gte('s.dateSubmitted', ":$date1"),
                $q->expr()->lte('s.dateSubmitted', ":$date2")
            );
            return array($expr, $parameters);
        } elseif (strpos($filter['column'], 'field.') !== false) {
            //form field so change up the query
            $idParam    = $this->generateRandomParameterName();
            $valueParam = $this->generateRandomParameterName();
            $sq = $this->getEntityManager()->createQueryBuilder();

            $f = $this->generateRandomParameterName();
            $r = $this->generateRandomParameterName();

            $subquery = $sq->select("count($r.id)")
                ->from('MauticFormBundle:Result', $r)
                ->leftJoin('MauticFormBundle:Field', $f,
                    Query\Expr\Join::WITH,
                    $sq->expr()->eq($f, "$r.field")
                )
                ->where(
                    $q->expr()->andX(
                        $q->expr()->eq("IDENTITY($r.field)", ":$idParam"),
                        $q->expr()->like("$r.value", ":$valueParam"),
                        $q->expr()->eq("$r.submission", 's')
                    )
                )
                ->getDql();
            $expr = $q->expr()->eq(sprintf("(%s)",$subquery), 1);

            $fieldId    = substr($filter['column'], 6);
            $parameters = array($idParam => $fieldId, $valueParam => "%{$filter['value']}%");
            return array($expr, $parameters);
        } else {
            return parent::getFilterExpr($q, $filter);
        }
    }
}
