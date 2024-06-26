<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine_AuditLog
 *
 * @package     Doctrine
 * @subpackage  AuditLog
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_AuditLog extends Doctrine_Record_Generator
{
    /**
     * Array of AuditLog Options
     *
     * @var array
     */
    protected $_options = array('className'         => '%CLASS%Version',
                                'version'           => array('name'   => 'version',
                                                             'alias'  => null,
                                                             'type'   => 'integer',
                                                             'length' => 8,
                                                             'options' => array('primary' => true)),
                                'tableName'        => '%TABLE%_Version',
                                'generateFiles'     => false,
                                'table'             => false,
                                'pluginTable'       => false,
                                'children'          => array(),
                                'auditLog'          => true,
                                'deleteVersions'    => true,
                                'cascadeDelete'     => true,
                                'excludeFields'     => array(),
                                'appLevelDelete'    => false);

    /**
     * Accepts array of options to configure the AuditLog
     *
     * @param   array $options An array of options
     */
    public function __construct(array $options = array())
    {
        $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
    }

    public function buildRelation()
    {
        $this->buildForeignRelation('Version');
        $this->buildLocalRelation();
    }

    /**
     * Set the table definition for the audit log table
     *
     */
    public function setTableDefinition(): void
    {
        $name = $this->_options['table']->getComponentName();

        // Building columns
        $columns = $this->_options['table']->getColumns();

        // remove all sequence, autoincrement and unique constraint definitions and add to the behavior model
        foreach ($columns as $column => $definition) {
            if (in_array($column, $this->_options['excludeFields'])) {
                continue;
            }
            unset($definition['autoincrement']);
            unset($definition['sequence']);
            unset($definition['unique']);

            $fieldName = $this->_options['table']->getFieldName($column);
            if ($fieldName != $column) {
                $name = $column . ' as ' . $fieldName;
            } else {
                $name = $fieldName;
            }

            $this->hasColumn($name, $definition['type'], $definition['length'], $definition);
        }

        // the version column should be part of the primary key definition
        $this->hasColumn(
	        $this->_options['version']['name'],
            $this->_options['version']['type'],
            $this->_options['version']['length'],
            $this->_options['version']['options']);
    }

    /**
     * Get array of information for the passed record and the specified version
     *
     * @param   Doctrine_Record $record
     * @param   integer         $version
     * @param   integer         $hydrationMode
     * @param	boolean			$asCollection
     * @return  array           An array or Doctrine_Collection or a Doctrine_Record
     */
    public function getVersion(Doctrine_Record $record, $version, $hydrationMode = Doctrine_Core::HYDRATE_ARRAY, $asCollection = true)
    {
        $className = $this->_options['className'];
        $method    = ($asCollection) ? 'execute' : 'fetchOne';

        $q = Doctrine_Core::getTable($className)
            ->createQuery();

        $values = array();
        foreach ((array) $this->_options['table']->getIdentifier() as $id) {
            $conditions[] = $className . '.' . $id . ' = ?';
            $values[] = $record->get($id);
        }

        $where = implode(' AND ', $conditions) . ' AND ' . $className . '.' . $this->_options['version']['name'] . ' = ?';

        $values[] = $version;

        $q->where($where);

        return $q->$method($values, $hydrationMode);
    }

    /**
     * Get the max version number for a given Doctrine_Record
     *
     * @param Doctrine_Record $record
     * @return Integer $versionnumber
     */
    public function getMaxVersion(Doctrine_Record $record)
    {
        if ($record->isNew()) {
            return 0;
        }
        $className = get_class($record);
        $select = $className . '.' . $this->_options['version']['name'] . ' max_version';

        foreach ((array) $this->_options['table']->getIdentifier() as $id) {
            $conditions[] = $className . '.' . $id . ' = ?';
            $values[] = $record->get($id);
        }
        if ($this->_options['table']->hasTemplate('SoftDelete')) {
            $columnName = $this->_options['table']->getTemplate('SoftDelete')->getOption('name');
            $conditions[] = '(' . $className . '.' . $columnName . ' IS NULL OR ' . $className . '.' . $columnName . ' IS NOT NULL)';
        }

        // Lock the version table using 'FOR UPDATE'
        $q = Doctrine_Core::getTable($className)
            ->createQuery()
            ->select($select)
            ->forUpdate()
            ->where(implode(' AND ',$conditions));


        $result = $q->execute($values, Doctrine_Core::HYDRATE_ARRAY);

        return isset($result[0]['max_version']) ? $result[0]['max_version']:0;
    }
}
