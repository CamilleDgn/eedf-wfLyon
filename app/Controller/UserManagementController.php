<?php

namespace Controller;

use \W\Controller\Controller;
use \W\Security\AuthentificationModel;
use \W\Model\UsersModel;

class UserManagementController extends Controller {

  protected $currentUser;
  protected $auth;
  protected $utils;
  protected $mail;

  public function __construct(){
    $this->currentUser = new UsersModel;
    $this->auth        = new AuthentificationModel;
    $this->mail        = new \PHPMailer();
  }

  public function listUsers(){

    $this->show('admin/manageUsers');
  }
  public function loginUser(){
    if($this->auth->isValidLoginInfo($_POST['email'], $_POST['password'])){
      $utilisateur = $this->currentUser->getUserByUsernameOrEmail($_POST['email']);
      $this->auth->logUserIn($utilisateur);
      $this->show('admin/manageUsers',['email'=>$_POST['email'], 'password'=>$_POST['password'], 'logged'=>$this->auth->getLoggedUser()]);
    } else {
      $this->show('admin/manageUsers', ['error'=>'incorrect']);
    }
  }
  public function inscription(){
    if($_SERVER['REQUEST_METHOD'] == 'GET'){
      $this->show('user/inscription');
    }else{
      if($this->currentUser->emailExists($_POST['email'])){
        $this->show('dev/output',['result'=>'il existe deja']);
        //$this->show('user/inscription',['error'=>'emailExists']);
      }else{
        $_POST['password'] = $this->auth->hashPassword($_POST['password']);
        $_POST['role'] = 'admin';
        $_POST['token'] = $randString = $this->utils->randomString();
        $newUser = $this->currentUser->insert($_POST);
        $this->auth->logUserIn($newUser);
        //$isSentEmail = $this->sendEmail($_POST['email'], $newUser['id'], $_POST['token']);
        //$this->show('dev/output',['result'=>'email sent','id'=>$newUser['id']]);
        $this->show('dev/output',['result'=>$newUser]);
      }
    }
  }

  public function connexion(){
    if($_SERVER['REQUEST_METHOD'] == 'GET'){
      $this->show('user/connexion');
    } else {
      $user = $this->auth->isValidLoginInfo($_POST['usernameOrEmail'], $_POST['password']);
      if($user != 0){
        $this->auth->logerUserIn($this->currentUser->find($user));
        $this->redirectToRoute('default_home');
      }else{
        $_SESSION['error'] = "Identifiant ou mot de passe incorrect";
        $this->redirectToRoute('login');
      }
    }
  }

  public function deconnexion(){
    $this->auth->logUserOut();
    $this->redirectToRoute('default_home');
  }

  public function login(){
    $this->show('admin/login');
  }

  public function confirmation(){
    $result = array();
    if($_GET['token']){
      $result['token'] = $_GET['token'];
    }
    if($_GET['id']){
      $result['id'] = $_GET['id'];
    }
    // Update on the database
    $data = array('confirm'=>1);
    $this->currentUser->update($data, $_GET['id']);
    $this->show('dev/output',['result'=>$result]);
  }

  private function sendEmail($address,$userId,$token){
    $this->mail->isSMTP();
    $this->mail->isHTML(true);
    $this->mail->Host = "smtp.gmail.com";
    $this->mail->Port = 465;
    $this->mail->SMTPAuth = true;
    $this->mail->SMTPSecure = 'ssl';
    $this->mail->Username  = "wf3lyon@gmail.com";
    $this->mail->Password = "Azerty1234";
    $this->mail->SetFrom('wf3lyon@gmail.com','BioForce3 Lyon');
    $this->mail->addAddress($address);
    $this->mail->Subject = 'EEDF Validation d\'email';
    $url = $this->generateTokenUrl($userId,$token);
    $bodyContent = '<p>Verification</p><a href="'.$url.'">'.$token.' '.$userId.'</a><p></p>';
    $this->mail->Body = $bodyContent;
    if (!$this->mail->send()) {
        return "Mailer Error: " . $this->mail->ErrorInfo;
    } else {
        return "Message sent!";
    }
  }

  private function generateTokenUrl($userId, $token){
    $url = 'http://localhost'.$this->generateUrl('admin_confirmation');
    $url .= '?id='   .$userId;
    $url .= '&token='.$token;
    return $url;
  }
}