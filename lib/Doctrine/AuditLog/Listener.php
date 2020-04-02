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
 * Doctrine_AuditLog_Listener
 *
 * @package     Doctrine
 * @subpackage  AuditLog
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_AuditLog_Listener extends Doctrine_Record_Listener
{
    /**
     * Instance of Doctrine_Auditlog
     *
     * @var Doctrine_AuditLog
     */
    protected $_auditLog;

    /**
     * Instantiate AuditLog listener and set the Doctrine_AuditLog instance to the class
     *
     * @param   Doctrine_AuditLog $auditLog 
     */
    public function __construct(Doctrine_AuditLog $auditLog) 
    {
        $this->_auditLog = $auditLog;
    }

    /**
     * Pre save event hook for incrementing version number
     *
     * @param   Doctrine_Event $event
     */
    public function preSave(Doctrine_Event $event)
    {
        $version = $this->_auditLog->getOption('version');
        $name = $version['alias'] === null ? $version['name'] : $version['alias'];

        $record = $event->getInvoker();
        $record->set($name, $this->_getNextVersion($record));
    }

    /**
     * Post save event hook for inserting new version record
     * This will only insert a version record if the auditLog is enabled
     *
     * @param   Doctrine_Event $event 
     */
    private function _postSave(Doctrine_Event $event)
    {
        if ($this->_auditLog->getOption('auditLog')) {
            $class = $this->_auditLog->getOption('className');

            $record  = $event->getInvoker();
            $version = new $class();
            $version->merge($record->toArray(false), false);
            $version->save();
        }
    }

    public function postUpdate(Doctrine_Event $event)
    {
        $this->_postSave($event);
    }

    public function postInsert(Doctrine_Event $event)
    {
        $this->_postSave($event);
    }

    /**
     * Pre delete event hook deletes all related versions
     * This will only delete version records if the auditLog is enabled
     *
     * @param   Doctrine_Event $event
     */
    public function preDelete(Doctrine_Event $event)
    {
        if ($this->_auditLog->getOption('auditLog')) {
	        $className = $this->_auditLog->getOption('className');
            $version = $this->_auditLog->getOption('version');
            $name = $version['alias'] === null ? $version['name'] : $version['alias'];
	        $event->getInvoker()->set($name, null);

            if ($this->_auditLog->getOption('deleteVersions')) {
    	        $q = Doctrine_Core::getTable($className)
    	            ->createQuery('obj')
    	            ->delete();
    	        foreach ((array) $this->_auditLog->getOption('table')->getIdentifier() as $id) {
    	            $conditions[] = 'obj.' . $id . ' = ?';
    	            $values[] = $event->getInvoker()->get($id);
    	        }

    	        $rows = $q->where(implode(' AND ', $conditions))
    					  ->execute($values);
    		}
        }
    }

    /**
     * Get the next version number for the audit log
     *
     * @param Doctrine_Record $record 
     * @return integer $nextVersion
     */
    protected function _getNextVersion(Doctrine_Record $record)
    {
        return ($this->_auditLog->getMaxVersion($record) + 1);
    }
}
