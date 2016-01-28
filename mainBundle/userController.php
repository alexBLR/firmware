<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadim
 * Date: 13.05.13
 * Time: 10:59
 * To change this template use File | Settings | File Templates.
 */

class userController
{
    public function __construct($method, $twig, $params)
    {
        global $bill_link;
        $this->dbConnect = $bill_link;
        $this->templater = $twig;
		$this->user = $_SESSION['user_id'];
        $method = $method . "Action";
        $this->$method($twig, $params);
    }

    public function listAction($twig, $params)
    {
        if($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
			$template = $twig->loadTemplate('users.html.twig');

			if($this->getRole() == 2 or $this->getRole() == 3) {
				$where = " where parrent = {$_SESSION['user_id']}";
			} else {
				$where = '';
			}
			$query = mysqli_query($this->dbConnect, "select login, role, summa, id, smena, credit, summaCredit from users {$where} order by id");
			if ($query) {
				while ($result = mysqli_fetch_assoc($query)) {
					$q = mysqli_query($this->dbConnect, "select sum(summa) as pay_summa from payLog where userId = '{$result['id']}'");
					$r = mysqli_fetch_assoc($q);
					if($r['pay_summa'] > 0) {
						$pays = 1;
					} else {
						$pays = 0;
					}

                    $clients = array();

                    $q = mysqli_query($this->dbConnect, "select * from users where parrent = '{$result['id']}'");
                    while ($r = mysqli_fetch_assoc($q)) {
                        $clients[] = array('login' => $r['login']);
                    }
					
					$users[] = array('id' => $result['id'], 'login' => $result['login'], 'summa' => $result['summa'], 'status' => $result['role'], 'group' => $result['smena'], 'role' => $result['role'], 'credit' => $result['credit'], 'summaCredit' => $result['summaCredit'], 'pays' => $pays, 'paySum' => $r['pay_summa'], 'clients' => $clients);
				}
			} else {
				throw new Exception("Error: mysqli error - " . mysqli_error($this->dbConnect));
			}

			$query = mysqli_query($this->dbConnect,"select name, id from groups {$where}");
			if ($query) {
				while ($result = mysqli_fetch_assoc($query)) {
					$groups[] = array('id' => $result['id'], 'name' => $result['name']);
				}
			} else {
				//throw new Exception("Error: mysqli error - " . mysqli_error());
			}

			$query = mysqli_query( $this->dbConnect,"select role, summa from users where id = '{$_SESSION['user_id']}'");
			$result = mysqli_fetch_assoc($query);


			echo $template->render(array('login' => $_SESSION['user_fio'], 'role' => $result['role'], 'summa' => $result['summa'], 'users' => $users, 'groups' => $groups));
        } else {
            header("Location: /");
        }
    }

    public function AddBalanceAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $summa = mysqli_real_escape_string($this->dbConnect, $_POST['amount']);
            $userId = intval($_POST['UserId']);
            $query = mysqli_query($this->dbConnect, "update users set summa = summa + {$summa} where id = '{$userId}'");
            $query = mysqli_query($this->dbConnect, "insert into payLog set summa = {$summa}, userId = '{$userId}', payerId = {$_SESSION['user_id']}");
        }

        header("Location: /users/");
    }

    public function setGroupAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $groups = intval($_POST['groups']);
            $userId = intval($_POST['userId']);
            if ($groups > 0) {
                $query = mysqli_query($this->dbConnect, "update users set smena = '{$groups}' where id = '{$userId}'");
            }
        }

        header("Location: /users/");
    }

    public function deleteUserAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $userId = intval($_POST['userId']);
            if($this->getRole() == 1) {
            	$query = mysqli_query($this->dbConnect, "delete from users where id = '{$userId}'");
            } else {
            	$query = mysqli_query($this->dbConnect, "delete from users where id = '{$userId}' and parrent = '{$this->user}'");
            }
            if ($query) {
                header("Location: /users/");
            }
        }
    }
	
	public function addLoginAction($twig, $params)
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $userId = intval($_POST['userId']);
                $login = $_POST['add_login'];
                $passwd = md5($_POST['add_password']);
                $query = mysqli_query( $this->dbConnect, "select id from users where login = '{$login}' limit 1");
                if ($this->is_email($login)) {
                    if (mysqli_num_rows($query) == 0) {
                        $query = mysqli_query($this->dbConnect, "insert into users set login = '{$login}', passwd = '{$passwd}', parrent = '{$this->user}' ");
                        header("Location: /users/");
                    } else {
						
                    }
                } else {
				
                }
            if ($query) {
                header("Location: /users/");
            }
        }
    }

    private function getRole()
    {
        $query = mysqli_query($this->dbConnect, "select role from users where id = '{$_SESSION['user_id']}'");
        $result = mysqli_fetch_assoc($query);

        return $result['role'];
    }

    public function setCreditAction()
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $userId = intval($_POST['UserId']);
            $amount = intval($_POST['amount']);
            $query = mysqli_query($this->dbConnect, "update users set credit = 1, summaCredit = '{$amount}' where id = {$userId}");
            if ($query) {
                header("Location: /users/");
            }
        }
    }
	
	
    public function deleteCreditAction()
    {
        if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
            $userId = intval($_POST['UserId']);
            $amount = intval($_POST['amount']);
            $query = mysqli_query($this->dbConnect, "update users set credit = 0, summaCredit = '0' where id = {$userId}");
            if ($query) {
                header("Location: /users/");
            }
        }
    }
	
	public function getPayAction() {
		if ($this->getRole() == 1 or $this->getRole() == 2 or $this->getRole() == 3) {
			$userId = intval($_POST['id']);
			$query = mysqli_query($this->dbConnect, "select * from payLog where userId = '{$userId}'");
			if(mysqli_num_rows($query) != 0) {
				$sOut = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">';
				while ($result = mysqli_fetch_assoc($query)) {
					$sOut .= "<tr><td>Сумма: {$result['summa']}</td><td>Дата: {$result['date']}</td></tr>";
				}
				$sOut .= '</table>';
				echo $sOut;
			} else {
				echo "Данных о зачислении нет";
			}
		}
	}
	
	 public function setStatusAction()
    {
        if ($this->getRole() == 1 or $this->getRole() == 3) {
            $userId = intval($_POST['user_id']);
            $status = intval($_POST['status']);
            $query = mysqli_query($this->dbConnect, "update users set role = '{$status}' where id = {$userId}");
            if ($query) {
                header("Location: /users/");
            }
        }
    }

    public function setPasswordAction() {
        if ($this->getRole() == 1) {
            $userId = intval($_POST['id']);
            $pass = md5($_POST['pass']);
            $query = mysqli_query($this->dbConnect ,"update users set passwd = '{$pass}' where id = '{$userId}' limit 1");
            echo "update users set passwd = '{$pass}' where id = '{$userId}' limit 1";

        }
    }
	
	public function is_email($email)
    {
        return preg_match("/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]{2,6})$/", $email);
    }
}