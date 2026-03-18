<?php
require_once './src/i18n.php';
$pageTitle = i18n_t('team.title', 'Team');
include './header.php';

require_once './src/team.php';

$teams = Team::findAll();
?>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="./team.php"><?php echo htmlspecialchars(i18n_t('team.title', 'Team'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars(i18n_t('common.overview', 'Overview'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <a class="btn btn-primary my-3" href="./newteam.php"><i class="fa fa-plus"></i> <?php echo htmlspecialchars(i18n_t('team.new', 'New Team'), ENT_QUOTES, 'UTF-8'); ?></a>

    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm table-mobile-stack" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars(i18n_t('common.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('common.created_on', 'Created at'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(i18n_t('common.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($teams as $team): ?>
                <?php $date = new DateTime($team->created_at); ?>
                <tr>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.name', 'Name'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($team->name, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.created_on', 'Created at'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($date->format('d-m-Y H:i:s'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="<?php echo htmlspecialchars(i18n_t('common.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?>" width="120">
                    <div class="btn-group" role="group">
                      <button id="teamAction<?php echo (int)$team->id; ?>" type="button" class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo htmlspecialchars(i18n_t('common.action', 'Action'), ENT_QUOTES, 'UTF-8'); ?>
                      </button>
                      <div class="dropdown-menu" aria-labelledby="teamAction<?php echo (int)$team->id; ?>">
                        <a class="dropdown-item" href="./add-team-member.php?team-id=<?php echo (int)$team->id; ?>"><?php echo htmlspecialchars(i18n_t('team.add_member', 'Add Member'), ENT_QUOTES, 'UTF-8'); ?></a>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include './footer.php'; ?>
