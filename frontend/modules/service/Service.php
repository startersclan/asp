<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2019, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

use System\Config;
use System\Controller;
use System\TimeHelper;
use System\TimeSpan;
use System\View;

/**
 * Service Module Controller
 *
 * @package Modules
 */
class Service extends Controller
{
    /**
     * @var ServiceModel
     */
    protected $serviceModel;

    /**
     * @var ServiceAjaxModel
     */
    protected $serviceAjaxModel;

    /**
     * @protocol    ANY
     * @request     /ASP/service/risingstar
     * @output      html
     */
    public function risingstar()
    {
        // Grab database connection
        $this->requireDatabase();

        // Load model
        $this->loadModel('ServiceModel', 'service');

        // Load view
        $view = new View('risingstar', 'service');

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/service/js/risingstar.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/modules/service/css/general.css");

        // Get last refresh and next refresh times
        $interval = Config::GetOrDefault('stats_risingstar_interval', 7);
        $last = Config::Get('stats_risingstar_refresh');
        $next = $last + (86400 * $interval);
        $isDue = ($next < time());

        // Create time spans
        $spanNext = TimeSpan::FromSeconds($next - time())->toString('%e days, %y hours');
        if ($isDue)
        {
            $spanNext .= " ago";
            $view->set('next_color', 'red');
        }
        else
        {
            $view->set('next_color', 'black');
        }

        // Set view variables
        $view->set('last', TimeHelper::FormatDifference($last, time()));
        $view->set('next', $spanNext);
        $view->set('records', $this->serviceModel->getRisingStarCount());

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/service/list
     * @output      json
     */
    public function postList()
    {
        // Grab database connection
        $this->requireDatabase(true);

        // We only accept these POST actions
        $this->requireAction("risingstar", "smoc", "general");

        // Load model
        $this->loadModel('ServiceAjaxModel', 'service');

        // Process action
        try
        {
            switch ($_POST['action'])
            {
                case "risingstar":
                    $data = $this->serviceAjaxModel->getRisingStarList($_POST);
                    echo json_encode($data);
                    break;
                default:
                    $this->sendJsonResponse(false, "Invalid Action");
                    break;
            }
        }
        catch (Exception $e)
        {
            // Log Exception
            System::LogException($e);

            // Send error message to client
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/service/cron
     * @output      json
     */
    public function postCron()
    {
        // Grab database connection
        $this->requireDatabase(true);

        // We only accept these POST actions
        $this->requireAction("risingstar", "smoc", "general");

        // Load model
        $this->loadModel('ServiceModel', 'service');

        // Process action
        try
        {
            switch ($_POST['action'])
            {
                case "risingstar":
                    $this->serviceModel->generateRisingStar();

                    // Save config
                    Config::Set('stats_risingstar_refresh', time());
                    Config::Save();

                    // Send response
                    $this->sendJsonResponse(true, "Action Completed");
                    break;
                default:
                    $this->sendJsonResponse(false, "Invalid Action");
                    break;
            }
        }
        catch (Exception $e)
        {
            System::LogException($e);
            $this->sendJsonResponse(false, $e->getMessage());
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/service/alerts
     * @output      json
     */
    public function postAlerts()
    {
        // We only accept these POST actions
        $this->requireAction("retrieve", "clear");

        // Load model
        $this->loadModel('ServiceModel', 'service');

        // Our list of alerts
        $messages = [];

        // Check for rising star leaderboard refresh
        if ($this->serviceModel->getNumRoundsPlayed() > 0)
        {
            $interval = Config::GetOrDefault('stats_risingstar_interval', 7);
            $last = Config::Get('stats_risingstar_refresh');
            $next = $last + (86400 * $interval);
            if ($next < time())
            {
                $spanNext = TimeSpan::FromSeconds($next - time());
                $message = 'The Rising Star Leaderboard update is past due';
                $message .= ($last > 0 && $spanNext->getWholeHours() > 0)
                    ? ' by ' . $spanNext->toString('%e days and %y hours')
                    : '!';
                $messages[] = ['Attention Required!', $message, '/ASP/service/risingstar'];
            }
        }

        // Check for new Sergeant Major of the Corps!
        if ($this->serviceModel->getNumOfEligibleSergeantMajors() > 0)
        {
            $interval = Config::GetOrDefault('stats_smoc_interval', 7);
            $last = Config::GetOrDefault('stats_smoc_refresh', 0);
            $next = $last + (86400 * $interval);
            if ($next < time())
            {
                $spanNext = TimeSpan::FromSeconds($next - time());
                $message = 'Selection of the next Sergeant Major of the Corps is past due';
                $message .= ($last > 0 && $spanNext->getWholeHours() > 0)
                    ? ' by ' . $spanNext->toString('%e days and %y hours')
                    : '!';
                $messages[] = ['Attention Required!', $message, '/ASP/service/smoc'];
            }
        }

        // Check for General Promotions!
        if ($this->serviceModel->getNumOfEligibleGenerals() > 0)
        {
            $interval = Config::GetOrDefault('stats_general_interval', 30);
            $last = Config::GetOrDefault('stats_general_refresh', 0);
            $next = $last + (86400 * $interval);
            if ($next < time())
            {
                $spanNext = TimeSpan::FromSeconds($next - time());
                $message = 'Selection of the next 4-Star General is past due';
                $message .= ($last > 0 && $spanNext->getWholeHours() > 0)
                    ? ' by ' . $spanNext->toString('%e days and %y hours')
                    : '!';
                $messages[] = ['Attention Required!', $message, '/ASP/service/general'];
            }
        }

        $this->sendJsonResponse(!empty($messages), $messages);
    }
}