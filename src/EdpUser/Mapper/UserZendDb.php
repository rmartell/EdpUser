<?php

namespace EdpUser\Mapper;

use EdpCommon\Mapper\DbMapperAbstract,
    EdpUser\Module,
    EdpUser\Model\UserInterface as UserModelInterface,
    ArrayObject;

class UserZendDb extends DbMapperAbstract implements UserInterface
{
    protected $tableName = 'user';
    protected $emailValidator;

    public function persist(UserModelInterface $user)
    {
        $data = new ArrayObject($user->toArray());
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('data' => $data, 'user' => $user));
        $db = $this->getWriteAdapter();
        if ($user->getUserId() > 0) {
            $db->update($this->getTableName(), (array) $data, $db->quoteInto('user_id = ?', $user->getUserId()));
        } else {
            $db->insert($this->getTableName(), (array) $data);
            $userId = $db->lastInsertId();
            $user->setUserId($userId);
        }
        return $user;
    }

    public function findByEmail($email)
    {
        $db = $this->getReadAdapter();
        $sql = $db->select()
            ->from($this->getTableName())
            ->where('email = ?', $email);
        $this->events()->trigger(__FUNCTION__ . '.pre', $this, array('query' => $sql));
        $row = $db->fetchRow($sql);
        $userModelClass = Module::getOption('user_model_class');
        $user = $userModelClass::fromArray($row);
        $this->events()->trigger(__FUNCTION__ . '.post', $this, array('user' => $user, 'row' => $row));
        return $user;
    }

    public function findByUsername($username)
    {
        $db = $this->getReadAdapter();
        $sql = $db->select()
            ->from($this->getTableName())
            ->where('username = ?', $username);
        $this->events()->trigger(__FUNCTION__, $this, array('query' => $sql));
        $row = $db->fetchRow($sql);
        $userModelClass = Module::getOption('user_model_class');
        return $userModelClass::fromArray($row);
    }

    public function findById($id)
    {
        $db = $this->getReadAdapter();
        $sql = $db->select()
            ->from($this->getTableName())
            ->where('user_id = ?', $id);
        $this->events()->trigger(__FUNCTION__, $this, array('query' => $sql));
        $row = $db->fetchRow($sql);
        $userModelClass = Module::getOption('user_model_class');
        return $userModelClass::fromArray($row);
    }

    public function getEmailValidator()
    {
        if (null === $this->emailValidator) {
            $this->emailValidator = array('Db\NoRecordExists', true, array(
                'adapter'   => $this->getReadAdapter(),
                'table'     => $this->getTableName(),
                'field'     => 'email'
            ));
        }
        return $this->emailValidator;
    }
}
