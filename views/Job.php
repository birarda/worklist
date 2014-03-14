<?php

use \Michelf\Markdown;

class JobView extends View {
    public $layout = 'NewWorklist';
    public $title = '%d: %s - Worklist';

    public $stylesheets = array(
        'css/workitem.css',
        'css/review.css',
        'css/favorites.css',
        'css/userinfo.css',
        'css/budget.css',
        'css/entries.css',
        'css/job.css'
   );

    public $scripts = array(
        'js/jquery/jquery.template.js',
        'js/jquery/jquery.jeditable.min.js',
        'js/jquery/jquery.tallest.js',
        'js/jquery/jquery.metadata.js',
        'js/jquery/jquery.blockUI.js',
        'js/ajaxupload/ajaxupload.js',
        'js/datepicker.js',
        'js/timepicker.js',
        'js/review.js',
        'js/favorites.js',
        'js/github.js',
        'js/skills.js',
        'js/entries.js',
        'js/job.js'
    );

    public function render() {
        $worklist = $this->worklist = $this->read('worklist');
        $this->workitem = $this->read('workitem');
        $this->user = $this->read('user');
        $this->workitem_project = $this->read('workitem_project');
        $this->title = sprintf($this->title, $worklist['id'], $worklist['summary']);

        if ($this->currentUser['id']) {
            $this->scripts[] = 'js/uploadFiles.js';
        }


        $this->bids = $this->read('bids');
        $this->fees = $this->read('fees');

        $this->order_by = $this->read('order_by');
        $this->action = $this->read('action');
        $this->action_error = $this->read('action_error');
        $this->classEditable = $this->read('classEditable');
        $this->allowEdit = (int) $this->read('allowEdit');
        $this->userHasRights = (int) $this->read('userHasRights');
        $this->isGitHubConnected = (int) $this->read('isGitHubConnected');
        $this->message = $this->read('message');
        $this->currentUserHasBid = (int) $this->read('currentUserHasBid');
        $this->has_budget = (int) $this->read('has_budget');
        $this->is_project_runner = (int) $this->read('is_project_runner');
        $this->is_project_founder = (int) $this->read('is_project_founder');
        $this->promptForReviewUrl =  (int) $this->read('promptForReviewUrl');
        $this->status_error =  (int) $this->read('status_error');
        $this->displayDialogAfterDone =  (int) $this->read('displayDialogAfterDone');
        $this->userinfotoshow =  (int) $this->read('userinfotoshow');


        $this->reviewer = $this->read('reviewer');

        return parent::render();
    }

    public function runSandboxCheck() {
        return $this->worklist['status'] == 'Functional' && $this->currentUser['is_runner'];
    }

    public function canUpload() {
    	return $this->worklist['status'] != 'Done' && (int) $this->currentUser['id'] > 0;
    }

    public function editing() {
        return $this->action == 'edit';
    }

    public function canEditAndNotEditing() {
        return $this->allowEdit && $this->action != 'edit';
    }

    public function canEditAndEditing() {
        return $this->allowEdit && $this->action == 'edit';
    }

    public function canChangeStatus() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
            (   !$this->workitem->getIsRelRunner() 
                || ($user->getIs_admin() == 1 && $is_runner) 
                || ($worklist['mechanic_id'] == $this->currentUser['id']) &&
                $worklist['status'] != 'Done'
            ) 
          || $workitem->getIsRelRunner()
          || ($worklist['creator_id']== $this->currentUser['user_id'] && $worklist['status'] != 'Done')
        );
    }

    public function editableStatusSelect() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        $statusListRunner = $this->read('statusListRunner');
        $statusListCreator = $this->read('statusListCreator');

        $ret = '';
        if (!
            ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) 
          &&($worklist['mechanic_id'] == $this->currentUser['id'])
          && $worklist['status'] != 'Done') 
        { 
            //mechanics
            foreach ($statusListMechanic as $status) {
                if ($status != $worklist['status']) {
                    $ret .= '<option value="' . $status . '">' . $status  . '</option>';
                }
            }
        } else if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) { 
            //runners and admins
            foreach ($statusListRunner as $status) {
                if ( $status != $worklist['status'] ) {
                    $ret .= '<option value="' .  $status . '">' . $status . '</option>';
                }
            }
        } else if (
             $worklist['creator_id']== $user_id 
          && $worklist['status'] != 'Working' 
          && $worklist['status'] != 'Functional' && $worklist['status'] != 'Review' 
          && $worklist['status'] != 'Completed' && $worklist['status'] != 'Done' 
        ) {
            //creator
            foreach ($statusListCreator as $status) {
                if (!($status == 'Suggested' && $worklist['status'] == 'SuggestedWithBid') && $status != $worklist['status']) {
                    $ret .= '<option value="' . $status . '">' . $status . '</option>';
                }
            }
        }

        return $ret;
    }

    public function jobBudget() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $ret = '';
        if (
            (
                $user->isRunnerOfWorkitem($workitem) 
              || (
                    array_key_exists('userid', $_SESSION)
                  && (
                        $_SESSION['userid'] == $worklist['budget_giver_id'] 
                      || strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false
                    ) 
                ) 
            ) 
          && !empty($worklist['budget_id'])
        ) {
            if ($this->action !="edit") {
                $ret = '<div class="job-budget">'
                    .    '<div class="project-label">Budget:</div>'
                    .    $worklist['budget_id'] . " - " . htmlspecialchars($worklist['budget_reason'])
                    . '</div>';
            }
        }
        return $ret;
    }

    public function statusDone() {
        return $this->worklist['status'] == "Done";
    }

    public function isGitProject() {
        return $this->workitem_project->getRepo_type() == 'git';
    }

    public function editableRunnerBox() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $runnerslist = Project::getAllowedRunnerlist($worklist['project_id']);
        $ret = ' ';
        if ($worklist['runner_nickname'] != 'Not funded') {
            $ret .= 
                '<a href="./user/' . $worklist['runner_id'] . '" target="_blank" id="ping-r-btn"' .
                ' title="' . (isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner") . '"' .
                ' data-user-id="' . $worklist['runner_id'] . '">' .
                    substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '') .
                '</a>';
        } else {
            $ret .= $worklist['runner_nickname'];
        }
        $ret .= '<span class="changeRunner"><select name="runner">';
        foreach ($runnerslist as $r) {
            $ret .= 
                '<option value="' . $r->getId() . '"' . (($worklist['runner_id'] == $r->getId()) ? ' selected="selected"' : '') . '>' . 
                    $r->getNickname() . 
                '</option>';
        }
        $ret .= 
            '</select>' .
            '<div class="buttonContainer"><input type="button" class="smbutton" name="changerunner" value="Change Runner" /></div>' .
            '<div class="buttonContainer"><input type="button" class="smbutton" name="cancel" value="Cancel" /></div>' .
            '</span>';
        return $ret;
    }

    public function nonEditableRunnerBox() {
        $worklist = $this->worklist;
        $ret = '';
        if ($worklist['runner_nickname'] != 'Not funded' && $worklist['runner_nickname'] != '') {
            $ret .= 
                '<span id="pingRunner" class="runnerName" title="' . (isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner") .'">' .
                '<a href="#">Runner:</a></span>' .
                '<a href="./user/' . $worklist['runner_id'] . '" target="_blank">' . 
                    substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '') . 
                '</a>';
        } else {
            $ret .= '<span class="runnerName" title="Ping Runner">Runner:</span> Not funded';
        }
        return $ret;
    }

    public function mechanicBox() {
        $worklist = $this->worklist;
        $fees = $this->fees;
        $mech = '';

        if( count($fees) >0 ) {
            foreach( $fees as $fee) {
                if ($fee['desc'] == 'Accepted Bid') {
                    $mech = $fee['nickname'];
                }
            }
        }
        if ($mech == '') {
            $mech = '<span class="mechanicName">Mechanic:</span>Not assigned';
        } else {
            $tooltip = isset($_SESSION['userid']) ? "Ping Mechanic" : "Log in to Ping Mechanic";
            $mech = 
                '<span id ="pingMechanic" class="mechanicName" title="' . $tooltip . '" >' . 
                  '<a href="#">Mechanic:</a>' . 
                '</span>' . 
                '<a id="ping-btn" href="./user/' . $worklist['mechanic_id'] . '" target="_blank">' . $mech . '</a>';
        }
        return $mech;
    }

    public function canEditSummary() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];

       return (
             (($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) && $worklist['status']!='Done') 
          || (
                 $worklist['creator_id'] == $this->currentUser['id']  
              && ($worklist['status']=='Suggested' || $worklist['status']=='SuggestedWithBid') 
              && is_null($worklist['runner_id'])
            )
        );
    }

    public function statusInfo() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        $statusListRunner = $this->read('statusListRunner');
        $statusListCreator = $this->read('statusListCreator');

        $ret = '';
        if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) || ($this->currentUser['id'] == $worklist['runner_id'])) {
            $ret .= '<select id="status" name="status" class="hidden">';
            foreach ($statusListRunner as $status) {
                $ret .= '<option value="' . $status . '"' . ($status == $worklist['status'] ? ' selected="selected"' : '') .'>' . $status . '</option>';
            }
            $ret .= '</select>';
        } else if ($worklist['creator_id'] == $this->currentUser['id'] && $mechanic_id == $user_id) {
            $ret .= '<select id="status" name="status">';
            foreach ($statusListCreator as $status) {
                $ret .= '<option value="' . $status . '"' . ($status == $worklist['status'] ? ' selected="selected"' : '') . '>' . $status . '</option>';
            }
            $ret .= '</select>';
        } else if ($$worklist['creator_id'] == $this->currentUser['id']) {
            $ret .= '<select id="status" name="status">';
            foreach ($statusListCreator as $status) {
                $ret .= '<option value="' . $status . '" ' . ($status == $worklist['status'] ? ' selected="selected"' : '') . '>' .  $status . '</option>';
            }
            $ret .= '</select>';
        } else { 
            $ret .= $worklist['status'] . ' <input type="hidden" id="status" name="status" value="' . $worklist['status'] . '" />';
        }
        return $ret;
    }


    public function canEditNotes() {
        $worklist = $this->worklist;
        $is_project_runner = $this->read('is_project_runner');
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (($is_project_runner || ($user->getIs_admin() == 1 && $is_runner) || $worklist['creator_id'] == $this->currentUser['id']) && ($worklist['status'] != 'Done'));
    }

    public function notesHtml() {
        $worklist = $this->worklist;
        return replaceEncodedNewLinesWithBr($worklist['notes']);
    }

    public function notesHtmlWithLinks() {
        $worklist = $this->worklist;
        return replaceEncodedNewLinesWithBr(linkify($worklist['notes']));
    }

    public function canSeeBudgetArea() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        return (
            $user->isRunnerOfWorkitem($workitem) 
          || $_SESSION['userid'] == $worklist['budget_giver_id']
          || strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false
        );
    }

    public function isRunnerOfWorkitem() {
        $workitem = $this->workitem;
        $user = $this->user;
        return $user->isRunnerOfWorkitem($workitem);
    }

    public function getBudgetCombo() {
        $worklist = $this->worklist;
        $user = $this->user;
        return $user->getBudgetCombo($worklist['budget_id']);
    }

    public function canEditSandboxUrlOnEdit() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (($workitem->getIsRelRunner() || $worklist['creator_id'] == $this->currentUser['id'] || ($user->getIs_admin() == 1 && $is_runner)) && ($worklist['status'] != 'Done'));
    }


    public function canEditSandboxUrl() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
             (strcasecmp($worklist['status'], 'Working') == 0 || strcasecmp($worklist['status'], 'Review') == 0 || strcasecmp($worklist['status'], 'Functional') == 0) 
          && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) ||($worklist['mechanic_id'] == $this->currentUser['id']))
        );
    }

    public function canViewDiff() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_project_founder = $this->read('is_project_founder');
        return (
             ($worklist['status'] == 'Functional' || $worklist['status'] == 'Review')
          && $worklist['sandbox'] != 'N/A' 
          || ($worklist['status'] == 'Working' && ($user->isRunnerOfWorkitem($workitem) || $is_project_founder || $user->getId() == $worklist['mechanic_id']))
        );
    }

    public function canEditProject() {
        $is_project_runner = $this->read('is_project_runner');
        $worklist = $this->worklist;
        return (
             ($is_project_runner || $worklist['creator_id'] == $this->currentUser['id'] || ($this->currentUser['is_admin'] && $this->currentUser['is_runner'])) 
          && ($worklist['status'] != 'Done')
        );
    }

    public function editableProjectSelect() {
        $worklist = $this->worklist;
        $filter = new Agency_Worklist_Filter();
        $filter->setProjectId($worklist['project_id']);
        return $filter->getProjectSelectbox('Select Project', 0, 'project_id', 'project_id');
    }

    public function projectUrl() {
        $worklist = $this->worklist;
        return Project::getProjectUrl($worklist['project_id']);
    }

    public function projectWebsiteUrl() {
        $worklist = $this->worklist;
        $project = new Project($worklist['project_id']);
        return $project->getWebsiteUrl();
    }

    public function activeBidsCount() {
        return count($this->read('activeBids'));
    }

    public function canComment() {
        return $this->currentUser['id'] && ($this->worklist['status'] != 'Done');
    }

    public function comments() {
        if ($this->order_by != 'DESC') {
            return $this->read('comments');
        } else {
            return array_reverse($this->read('comments'), true);
        }
        
    }

    public function isDescOrder() {
        return $this->order_by == 'DESC';
    }

    public function skillsCount() {
        return count($this->workitem->getSkills());
    }

    public function commaSeparatedSkills() {
        return implode(', ', $this->workitem->getSkills());
    }

    public function isBug() {
        return $this->workitem->getBugJobId()>0 || $this->workitem->getIs_bug() == 1;
    }

    public function canReview() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
             ($this->currentUser['id'] > 0 && $user->isEligible() && $worklist['mechanic_id'] != $this->currentUser['id'])
          && (
                 $worklist['status'] == 'Review' 
              && (! $workitem->getCRCompleted())
              && (
                     (! $workitem->getCRStarted()) 
                  || $this->currentUser['id'] == $workitem->getCReviewerId() 
                  || $workitem->getIsRelRunner() 
                  || ($user->getIs_admin() == 1 && $is_runner)
                )
            )
        );
    }

    public function canEndReview() {
        $workitem = $this->workitem;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return 
             $this->read('userHasRights') 
          && (
                 $this->currentUser['id'] == $workitem->getCReviewerId() 
              || $workitem->getIsRelRunner() 
              || ($user->getIs_admin() == 1 && $is_runner)
            );
    }

    public function canBid() {
        $worklist = $this->worklist;
        return $worklist['status'] == 'Bidding' 
          || $worklist['status'] == 'SuggestedWithBid' 
          || ($worklist['status'] == 'Suggested' && $worklist['creator_id'] == $this->currentUser['id']);
    }

    public function userIsEligible() {
        return $this->user->isEligible();
    }

    public function canAcceptBids() {
        $bids = $this->read('bids');
        $workitem = $this->workitem;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
             (!empty($bids)) 
          && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) || $this->currentUser['id'] == $workitem->getRunnerId()) 
          && count($bids) >1 
          && !$workitem->hasAcceptedBids()
          && ((($workitem->getStatus()) == "Bidding") || $workitem->getStatus() == "SuggestedWithBid")
        );
    }

    public function bidsList() {
        $bids = $this->read('bids');
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        $is_project_runner = $this->read('is_project_runner');

        $ret = '';
        foreach($bids as $bid) {
            $biddings = array();
            if ($user->getId() == $bid['bidder_id'] && $bid['expires'] <= 0) {
                $biddings['0'] = $bid;
            } else if ($user->getId() != $bid['bidder_id'] && $bid['expires'] < 0) {
                $biddings = array();
            } else {
                $biddings['0'] = $bid;
            }

            foreach($biddings as $key=>$bid) {
                if ($user->getId() == $bid['bidder_id'] && $bid['expires'] < 0) {
                    $expired_class = ' expired_warn';
                } else {
                    $expired_class = '';
                }
                $canSeeBid = $user->getIs_admin() == 1 || $is_project_runner || $user->isRunnerOfWorkitem($workitem) ||
                             $user->getId() == $bid['bidder_id'] || ($worklist['status'] == 'SUGGESTEDwithBID' && $workitem->getIsRelRunner());
                $row_class = "";
                $row_class .= ($this->currentUser['id']) ? 'row-bidlist-live ' : '' ;
                $row_class .= ($view_bid_id == $bid['id']) ? ' view_bid_id ' : '' ;
                $row_class .= 'biditem';
                $row_class .= ($canSeeBid)
                            ? "-" . $bid['id'] . ' clickable'
                            : '';
                $row_class .= $expired_class;
                $ret .= '<tr class="' . $row_class . '">';

                // store bid info into jquery metadata so we won't have to fetch it again on user click
                // but only if user is runner or creator 15-MAR-2011 <godka>
                $notes = addcslashes(preg_replace("/\r?\n/", "<br />", $bid['notes']),"\\\'\"&\n\r<>");

                if ($canSeeBid) {
                    $ret .= 
                        "<script type='data'>".
                            "{id: {$bid['id']}, " .
                            "nickname: '{$bid['nickname']}', " .
                            "email: '{$bid['email']}', " .
                            "amount: '{$bid['bid_amount']}', " .
                            "bid_accepted: '{$bid['bid_accepted']}', " .
                            "bid_created: '{$bid['bid_created']}', " .
                            "bid_expires: '" . ($bid['expires'] ? relativeTime($bid['expires']) : "Never") . "', " .
                            "time_to_complete: '{$bid['time_to_complete']}', " .
                            "done_in: '{$bid['done_in']}', " .
                            "bidder_id: {$bid['bidder_id']}, " .
                            "notes: '" .  replaceEncodedNewLinesWithBr($notes) . "'}" .
                        "</script>";
                }
                $ret .= 
                     '<td>'
                    .  '<span class="table-back">'
                    .    '<span>'
                    .      (
                            $canSeeBid 
                              ? '<a href="#" bidderId="' . $bid['bidder_id'] . '" class="CreatorPopup">' . getSubNickname($bid['nickname']) . '</a>' 
                              : $bid['nickname']
                            )
                    .    '</span>'
                    .  '</span>'
                    .'</td>'
                    .'<td class="money"><span class="table-back"><span class="moneyPaddingSmall">$ ' . $bid['bid_amount'] . '</span></span></td>'
                    .'<td class="money"><span class="table-back"><span class="moneyPaddingSmall">' .$bid['done_in'] . '</span></span></td>';

                $ret .= '</tr>';
            }
        }
        if (!$ret) {
            $ret = '<tr><td style="text-align: center;" colspan="3"><span class="table-back"><span>No bids yet.</span></span></td></tr>';
        }
        return $ret;
    }

    function userIsMechanic() {
        return $this->worklist['mechanic_id'] == $this->currentUser['id'];
    }

    function feesList() {
        if (!$this->fees) {
            return '';
        }
        $fees = $this->fees;
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
 
        $feeTotal = 0;
        foreach($fees as $fee) {
            $feeTotal += (float) $fee['amount'];
            $date = explode("/", $fee['date']);
            $ret = 
                '<tr class="row-feelist-live">' .
                    '<script type="data">' .
                        "{id: {$fee['id']}, " .
                        "nickname: '{$fee['nickname']}', " .
                        "user_id: '{$fee['user_id']}', " .
                        "amount: '{$fee['amount']}', " .
                        "fee_created: '{$fee['date']}', " .
                        "desc:\"" .  $fee['desc'] . "\"}" .
                    '</script>' .
                    '<td class="nickname who">' .
                        '<a href="./user/' . $fee['user_id'] . '" target="_blank" title="' . $fee['nickname'] . '">' .
                            getSubNickname($fee['nickname'], 8) .
                        '</a>' .
                    '</td>' .
                    '<td class="fee">' .
                        '$' . $fee['amount'] . 
                    '</td>' .
                    '<td class="pre fee-description what"><div class="arrow"></div></td>' .
                    '<td class="when">' .
                        date( "M j", mktime(0, 0, 0, $date[0], $date[1], $date[2])) .
                    '</td>' .
                    '<td class="paid">' .
                        (
                            $this->currentUser['is_payer']
                                ?
                                    '<a href="#" class = "paid-link" id="feeitem-' . $fee['id'] . '">' .
                                        ($fee['paid'] == 0 ? "No" : "Yes") .
                                    '</a>'
                                :
                                    $fee['paid'] == 0 ? "No" : "Yes"
                        ) .
                        (
                            (
                                 $worklist['status'] != 'Done'
                              && (
                                    (
                                         $workitem->getIsRelRunner() 
                                      || ($user->getIs_admin() == 1 && $this->currentUser['is_runner'])
                                      || $this->currentUser['id'] == $workitem->getRunnerId() 
                                      || $this->currentUser['id'] == $fee['user_id']
                                    ) 
                                  && ($this->currentUser['id'] && empty($fee['paid']))
                                )
                            )
                                ? '<a href="#" id="wd-' . $fee['id'] . '" class="wd-link" title="Delete Entry">delete</a>' : ''
                        ) .
                    '</td>' .
                '</tr>';
            if ($fee['desc'] == 'Accepted Bid' && ($worklist['status'] == 'Review' || $worklist['status'] == 'Completed' || $worklist['status'] == 'Done')) {
                $ret .=
                    '<tr>' .
                        '<td colspan="5" class="bid-notes">' .
                            '<p><strong>Bid Notes:</strong>' . $fee['bid_notes'] . '</p>' .
                        '</td>' .
                    '</tr>';
            }
        }
        $ret .=
            '<tr id="job-total">' .
                '<td colspan="5">' .
                        '<h5>Job Total :</h5>' .
                        '<span class="data">$ ' . number_format($feeTotal, 2)  . '</span>' .
                '</td>' .
            '</tr>';
        return $ret;
    }

    public function userIsFollowing() {
        return (int) $this->workitem->isUserFollowing($this->currentUser['id']);
    }

    public function showAcceptBidButton() {
        $worklist = $this->worklist;
        $is_project_runner = $this->read('is_project_runner');
        $user = $this->user;
        return (int) (
            $is_project_runner 
          || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']) 
          || (isset($worklist['runner_id']) && $this->currentUser['id'] == $worklist['runner_id'])
        );
    }

    public function hasAcceptedBids() {
        return (int) $this->workitem->hasAcceptedBids();
    }

    public function insufficientRightsToEdit() {
        $worklist = $this->worklist;
        return (int) (
            (!$worklist['status'] == 'Suggested' || !$worklist['status'] == 'SuggestedWithBid') 
          && !$worklist['creator_id'] == $this->currentUser['id']
        );
    }

    public function showPingBidderButton() {
        $worklist = $this->worklist;
        $is_project_runner = $this->read('is_project_runner');
        $user = $this->user;

        return (int) (
            ($worklist['status'] == 'Bidding' && ($is_project_runner || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']))
          ||(isset($worklist['runner_id']) && $user_id == $worklist['runner_id']))
        );
    }

    public function showWithdrawOrDeclineButtons() {
        $worklist = $this->worklist;
        return (int) (
             $worklist['status'] != 'Done' 
          && $worklist['status'] != 'Working' 
          && $worklist['status'] != 'Functional' 
          && $worklist['status'] != 'Review' 
          && $worklist['status'] != 'Completed'
        );
    }

    public function showReviewUrlPopup() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        return (int) (
            ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']) || ($worklist['mechanic_id'] == $this->currentUser['id'])) &&
            (strcasecmp($worklist['status'], 'Done') != 0 && strcasecmp($worklist['status'], 'Completed') != 0)
        );
    }

    public function canReassignRunner() {
        $workitem = $this->workitem;
        $user = $this->user;
        return (int) ($this->action == "edit" && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $this->currentUser['is_runner'])));
    }

    public function crFee() {
        return number_format($this->read('crFee'), 2);
    }

    public function statusSuggested() {
        return $this->worklist['status'] == "Suggested";
    }

    public function statusSuggestedAny() {
        return $this->worklist['status'] == "Suggested" || $this->worklist['status'] == "SuggestedWithBid";
    }

    public function canFeeOthers() {
        return ($this->currentUser['is_runner'] || $this->is_project_founder || $this->is_project_runner);
    }

    public function userFeeSelectBox() {
        $filter = new Agency_Worklist_Filter();
        return $filter->getUserSelectbox(1, false, 'mechanicFee', 'mechanicFee');
    }

    public function userTipSelectBox() {
        $filter = new Agency_Worklist_Filter();
        echo $filter->getUserSelectbox(1, false, 'mechanicTip', 'mechanicTip');
    }

    public function showBidderStatistics() {
        return ($this->user->getIs_admin() == 1 && $this->currentUser['is_runner'] || $this->is_project_founder || $this->is_project_runner);
    }

    public function showBudgetSelect() {
        return ($this->currentUser['is_runner'] || $this->is_project_founder || $this->workitem->getIsRelRunner());
    }

    public function maxTip() {
        $max_tip = 0;
        foreach ($this->read('fees') as $fee) {
            if ($fee['desc'] == 'Accepted Bid') {
                $max_tip = $fee['amount'];
                break;
            }
        }
        return $maxTip;
    }

    public function mechanicNickname() {
        $row = $this->workitem->getUserDetails($this->worklist['mechanic_id']);
        return empty($row) ? '' : $nickname = $row['nickname'];
    }

    public function addBidMsg() {
        return ($this->isGitProject() && $this->read('isGitHubConnected')) ? 'Authorize GitHub app' : 'Add my bid';
    }

    public function taskEntries() {
        $entries = $this->read('taskEntries');
        $ret = '';
        $now = 0;
        foreach($entries as $entry) {
            if (!$now) {
                $now = strtotime(Model::now());
            }
            $id = $entry->id;
            $type = 'worklist';
            $date = strtotime($entry->date);
            $content = self::formatEntry($entry);

            $ret .= 
                  '<li entryid="' . $id . '" date="' . $date  . '" type="' . $type .  '">'
                .     '<h4>' . relativeTime($date - $now) . '</h4>'
                .     $content
                . '</li>';
        }
        return $ret;
    }

    /**
     * same than StatusView::formatWorklistEntry
     * @todo: integrate with StatusView::formatWorklistEntry
     */
    static function formatEntry($entry) {
        $ret = $entry->entry;

        // will only process new entries since #19490 deployment
        // @todo, remove this condition once history is removed/older
        if (strtotime($entry->date) > strtotime('2014-03-06 00:00:00')) {
            // linkify mentions and tasks references
            $ret = $ret;
            $mention_regex = '/(^|\s)@(\w+)/';
            $task_regex = '/(^|\s)\*\*#(\d+)\*\*/';
            $ret = preg_replace($mention_regex, '\1[\2](./user/\2)', $entry->entry);
            $ret = preg_replace($task_regex, '\1[\2](./\2)', $ret);
            // proccesed entries are returned as markdown-processed html
            $ret = Markdown::defaultTransform($ret);
        }

        return $ret;
    }
}