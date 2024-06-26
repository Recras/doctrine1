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
 * Doctrine_File_Index
 *
 * @package     Doctrine
 * @subpackage  File
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.doctrine-project.org
 * @since       1.0
 */
class Doctrine_File_Index extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('keyword', 'string', 255, array('notnull' => true,
                                                         'primary' => true));
                                                         
        $this->hasColumn('field', 'string', 50, array('notnull' => true,
                                                      'primary' => true));

        $this->hasColumn('position', 'string', 255, array('notnull' => true,
                                                          'primary' => true));
                                                          
        $this->hasColumn('file_id', 'integer', 8, array('notnull' => true,
                                                        'primary' => true));
    }

    public function setUp(): void
    {
        $this->hasOne('Doctrine_File', array('local' => 'file_id',
                                             'foreign' => 'id',
                                             'onDelete' => 'CASCADE',
                                             'onUpdate' => 'CASCADE'));
    }
}
