<?php

namespace Payroll\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Payroll\Model\WorkDone;
use Payroll\Form\WorkDoneForm;
use Payroll\Model\Pay;
use Payroll\Model\PayDetails;

class WorkDoneController extends AbstractActionController
{
  protected $workDoneTable;
  protected $personnelTaskTable;
  protected $personnelTable;
  protected $taskTable;
  protected $locationTable;
  protected $payTable;
  protected $deductionTable;
  protected $payDeductionTable;
  protected $payDetailsTable;

  /*
  Corresponds to the index of the route.
  */
  public function indexAction()
  {
    /*
    With Zend Framework 2, in order to set variables in the view, we return a ViewModel instance where the first parameter
    of the constructor is an array from the action containing data we need. These are then automatically passed to the view
    script. The ViewModel object also allows us to change the view script that is used, but the default is to use
    {controller name}/{action name}. We can now fill in the index.phtml view script.
    */
    return new ViewModel(array(
      'works' => $this->getWorkDoneTable()->fetchAll(),
      'personnelTaskTable' => $this->getPersonnelTaskTable(),
      'locationTable' => $this->getLocationTable(),
      'personnelTable' => $this->getPersonnelTable(),
      'taskTable' => $this->getTaskTable(),
    ));
  }

  /*
  Corresponds to the action:add of the route.
  */
  public function addAction()
  {
    $form = new WorkDoneForm();

    /*
    Gets a list of all personnel-task assignments in the database table
    and setPersonnelTaskArray which adds a select input to the form created with
    the selection list being that of the personnel-tasks' full name and id along with the rate.
    */
    //////////////////////////////////////////////////
    $personnelTasks = $this->getPersonnelTaskTable()->fetchAll();
    $personnelTask_array = array();
    foreach ($personnelTasks as $personnelTask) :
      $personnel = $this->getPersonnelTable()->getPersonnel($personnelTask->personnelId);
      $task = $this->getTaskTable()->getTask($personnelTask->taskId);
      $personnelTask_array[$personnelTask->personnelTaskId] = $personnel->personnelId.' :: '.$personnel->fName.' '.$personnel->lName
      .' - '.$task->taskName.'($'.$personnelTask->rate.')';
    endforeach;
    $form->setPersonnelTaskArray($personnelTask_array);
    //////////////////////////////////////////////////////


    /*
    Gets a list of all locations in the database table
    and setLocationArray which adds a select input to the form created with
    the selection list being that of the locations' name.
    */
    /////////////////////////////////////////////////////
    $locations = $this->getLocationTable()->fetchAll();
    $location_array = array();
    foreach ($locations as $location) :
      $location_array[$location->locationId] = $location->locationName;
    endforeach;
    $form->setLocationArray($location_array);
    /////////////////////////////////////////////////////

    $form->get('submit')->setValue('Add');

    $request = $this->getRequest();
    if ($request->isPost()) {
      $workDone = new WorkDone();
      $form->setInputFilter($workDone->getInputFilter());
      $form->setData($request->getPost());

      if ($form->isValid()) {

        $workDone->exchangeArray($form->getData());

        $this->getWorkDoneTable()->saveWorkDone($workDone);

        return $this->redirect()->toRoute('work-done');
      }
    }
    return array('form' => $form);
  }

  /*
  Corresponds to the action:edit of the route.
  */
  public function editAction()
  {
    $id = (int) $this->params()->fromRoute('id',0);
    /* If id from route/action/id is zero redirect to add form. */
    if (!$id) {
      return $this->redirect()->toRoute('work-done',array(
        'action' => 'add'
      ));
    }

    try {
      $workDone = $this->getWorkDoneTable()->getWorkDone($id);
    }
    catch (\Exception $ex) {
      return $this->redirect()->toRoute('work-done',array(
        'action' => 'index'
      ));
    }

    $form = new WorkDoneForm();

    /*
    Gets a list of all personnel-task assignments in the database table
    and setPersonnelTaskArray which adds a select input to the form created with
    the selection list being that of the personnel-tasks' full name and id along with the rate.
    */
    //////////////////////////////////////////////////
    $personnelTasks = $this->getPersonnelTaskTable()->fetchAll();
    $personnelTask_array = array();
    foreach ($personnelTasks as $personnelTask) :
      $personnel = $this->getPersonnelTable()->getPersonnel($personnelTask->personnelId);
      $task = $this->getTaskTable()->getTask($personnelTask->taskId);
      $personnelTask_array[$personnelTask->personnelTaskId] = $personnel->personnelId.' :: '.$personnel->fName.' '.$personnel->lName
      .' - '.$task->taskName.'($'.$personnelTask->rate.')';
    endforeach;
    $form->setPersonnelTaskArray($personnelTask_array);
    //////////////////////////////////////////////////////

    /*
    Gets a list of all locations in the database table
    and setLocationArray which adds a select input to the form created with
    the selection list being that of the locations' name.
    */
    /////////////////////////////////////////////////////
    $locations = $this->getLocationTable()->fetchAll();
    $location_array = array();
    foreach ($locations as $location) :
      $location_array[$location->locationId] = $location->locationName;
    endforeach;
    $form->setLocationArray($location_array);
    /////////////////////////////////////////////////////

    /* Sets the forms to the data in the work done model */
    $form->get('work_id')->setAttribute('value',$workDone->workId);
    $form->get('personnel_task_id')->setAttribute('value',$workDone->personnelTaskId);
    $form->get('date_done')->setAttribute('value',$workDone->dateDone);
    $form->get('hrs_worked')->setAttribute('value',$workDone->hoursWorked);
    $form->get('location_id')->setAttribute('value',$workDone->locationId);
    $form->get('submit')->setAttribute('value','Save');

    $request = $this->getRequest();
    if ($request->isPost()) {
      $form->setInputFilter($workDone->getInputFilter());
      $form->setData($request->getPost());

      if ($form->isValid()) {

        $workDone->exchangeArray($form->getData());

        $this->getWorkDoneTable()->saveWorkDone($workDone);

        return $this->redirect()->toRoute('work-done',array(
          'action' => 'index'
        ));
      }
    }

    /* Pass these key value pairings as identifiers into the edit view. */
    return array(
      'id' => $id,
      'form' => $form,
    );

  }

  /*
  Corresponds to the action:delete of the route.
  */
  public function deleteAction()
  {
    $id = (int) $this->params()->fromRoute('id',0);
    /* If id from route/action/id is zero redirect to route index. */
    if (!$id) {
      return $this->redirect()->toRoute('work-done');
    }

    $request = $this->getRequest();
    /* If request method is POST and 'del' is Yes delete row from database table. */
    if ($request->isPost()) {
      $del = $request->getPost('del','No');

      if ($del == 'Yes') {
        $id = (int) $request->getPost('id');
        $this->getWorkDoneTable()->deleteWorkDone($id);
      }

      return $this->redirect()->toRoute('work-done');
    }

    /* Pass these key value pairings as identifiers into the delete view. */
    return array(
      'id' => $id,
      'workDone' => $this->getWorkDoneTable()->getWorkDone($id)
    );
  }

  /*
  Calculates total pay for each worked done by employee in the last period.
  */
  public function calculateAction()
  {
    $lastPeriod = 0;
    $year = 0;

    //Auto calculate current period based current date time
    $startDate = date_create(date('Y').'-01-01');
    $today = date_create(date('Y-m-d'));
    $periodDuration = 14;
    $difference = (date_diff($startDate,$today)->format("%a"));
    $currentPeriod = ((int)floor($difference/14))+1;
    ////////////////////////

    if ($currentPeriod==1) {
      $lastPeriod = 27; //There are 27 periods in a year.
      $year = ((int) date('Y')) - 1;
    }
    else {
      $lastPeriod = $currentPeriod-1;
      $year = ((int) date('Y'));
    }


    $worksDoneLastPeriod=$this->getWorkDoneTable()->fetchAll2($lastPeriod); // Get records of workdone from the last period
    $pays = array();

    /* Find the works done by each individual and find the total pay to be reached */
    foreach ($worksDoneLastPeriod as $work) :
      $personnelTask = $this->getPersonnelTaskTable()->getPersonnelTask($work->personnelTaskId);
      $personnelId = $personnelTask->personnelId;
      $rate = $personnelTask->rate;
      $hoursWorked = $work->hoursWorked;

      if (isset($pays[$personnelId])) {
        $pays[$personnelId] = $pays[$personnelId] + ($rate * $hoursWorked);
      }
      else {
        $pays[$personnelId] = ($rate * $hoursWorked);
      }
    endforeach;

    $deductions = $this->getDeductionTable()->fetchAll2(1); //Get the periodic taxes and deductions that are applied to all employees every period
    $deductionPercentagesTotal = 0.0;
    $deductionFixedAmountTotal = 0.0;
    $payDetails = new PayDetails();
    $payDetails->descriptionOfWork = "";
    $payDetails->appliedDeductions = "";

    foreach ($deductions as $deduction):
      if ($deduction->fixedAmount){
        $deductionFixedAmountTotal += $deduction->fixedAmount;
        $payDetails->appliedDeductions .= $deduction->deductionName.'@$'.$deduction->fixedAmount.',';
      }
      else {
        $deductionPercentagesTotal += $deduction->deductionPercentage;
        $payDetails->appliedDeductions .= $deduction->deductionName.'@'.$deduction->deductionPercentage.'%,';
      }
    endforeach;
    $payDetails->appliedDeductions = rtrim($payDetails->appliedDeductions,",");


    $otherDeductions = $this->getDeductionTable()->fetchAll2(0);
    $otherDeductionsTotal = 0.0;


    /* create and save pay */
    $ids = array_keys($pays);
    foreach ($ids as $id) :
      $payCheck = new Pay();
      $payCheck->payId = 0;
      $payCheck->personnelId = $id;
      $personnel = $this->getPersonnelTable()->getPersonnel($id);

      foreach ($otherDeductions as $otherDeduction):
        # code...
        $deductions = $this->getPayDeductionTable()->fetchAll2($id);
        foreach ($deductions as $deduction) :
          # code...
          if ($deduction->duration == 0){
            break;
          }
          if ($deduction->deductionPercentage){
            $otherDeductionsTotal += ($pays[$id]*($deduction->deductionPercentage/100.00));
            $payDetails->appliedDeductions .= ','.$deduction->deductionName.'@'.$deduction->deductionPercentage.'%,';
          }
          else {
            $otherDeductionsTotal += $deduction->fixedAmount;
            $payDetails->appliedDeductions .= ','. $deduction->deductionName.'@$'.$deduction->fixedAmount.',';
          }
        endforeach;
      endforeach;
      $payDetails->appliedDeductions = rtrim($payDetails->appliedDeductions,",");


      $personnelTasks = $this->getPersonnelTaskTable()->fetchAll2($payCheck->personnelId);
      foreach ($personnelTasks as $personnelTask):
        $task = $this->getTaskTable()->getTask($personnelTask->taskId);
        $payDetails->descriptionOfWork .= $task->taskName.",";
      endforeach;
      $payDetails->descriptionOfWork = rtrim($payDetails->descriptionOfWork,",");

      $payCheck->grossAmount = $pays[$id];
      $payCheck->netAmount = $pays[$id] - ($deductionFixedAmountTotal+($pays[$id]*($deductionPercentagesTotal/100.00))+$otherDeductionsTotal);
      $payCheck->period = $lastPeriod;
      $payCheck->year = $year;

      $payDetails->personnelId = $payCheck->personnelId;
      $payDetails->grossAmount = $payCheck->grossAmount;
      $payDetails->netAmount = $payCheck->netAmount;
      $payDetails->period = $payCheck->period;
      $payDetails->year = $payCheck->year;

      $this->getPayTable()->savePay($payCheck);
      $savedPay = $this->getPayTable()->getPay2($payCheck->personnelId,$payCheck->period,$payCheck->year);
      $payDetails->payDetailsId = (int) $savedPay->payId;
      if ($payDetails->payDetailsId > 0) {
        $this->getPayDetailsTable()->createPayDetails($payDetails);
      }
      else {
        throw new \Exception('PayDetails id is null');
      }

    endforeach;

    return $this->redirect()->toRoute('pay');
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getWorkDoneTable()
  {
    if (!$this->workDoneTable) {
      $sm = $this->getServiceLocator();
      $this->workDoneTable = $sm->get('Payroll\Model\WorkDoneTable');
    }
    return $this->workDoneTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getPersonnelTaskTable()
  {
    if (!$this->personnelTaskTable) {
      $sm = $this->getServiceLocator();
      $this->personnelTaskTable = $sm->get('Payroll\Model\PersonnelTaskTable');
    }
    return $this->personnelTaskTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getPersonnelTable()
  {
    if (!$this->personnelTable) {
      $sm = $this->getServiceLocator();
      $this->personnelTable = $sm->get('Payroll\Model\PersonnelTable');
    }
    return $this->personnelTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getTaskTable()
  {
    if (!$this->taskTable) {
      $sm = $this->getServiceLocator();
      $this->taskTable = $sm->get('Payroll\Model\TaskTable');
    }
    return $this->taskTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getLocationTable()
  {
    if (!$this->locationTable) {
      $sm = $this->getServiceLocator();
      $this->locationTable = $sm->get('Payroll\Model\LocationTable');
    }
    return $this->locationTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getPayTable()
  {
    if (!$this->payTable) {
      $sm = $this->getServiceLocator();
      $this->payTable = $sm->get('Payroll\Model\PayTable');
    }
    return $this->payTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getDeductionTable()
  {
    if (!$this->deductionTable) {
      $sm = $this->getServiceLocator();
      $this->deductionTable = $sm->get('Payroll\Model\DeductionTable');
    }
    return $this->deductionTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getPayDeductionTable()
  {
    if (!$this->payDeductionTable) {
      $sm = $this->getServiceLocator();
      $this->payDeductionTable = $sm->get('Payroll\Model\PayDeductionTable');
    }
    return $this->payDeductionTable;
  }

  /*
  This method uses the services manager to get an instance of a Table object to
  access data in the database. This instance can be used throughout the Controller
  without creating new instances.
  */
  public function getPayDetailsTable()
  {
    if (!$this->payDetailsTable) {
      $sm = $this->getServiceLocator();
      $this->payDetailsTable = $sm->get('Payroll\Model\PayDetailsTable');
    }
    return $this->payDetailsTable;
  }

}
