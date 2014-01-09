<h2><?php echo __('Revisions'); ?></h2>
  <form method="post" action="" class="nomargin" id="revisions">
    <div class="log">
      <?php if (empty($this->revisions) ) { ?>
        <div class="alert alert-info"><?php echo __('No revisions in #{path}', array('path' => '<strong>' . REVISIONS_PATH . '</strong>')) ?></div>
      <?php } ?>
    </div>

    <table class="table table-condensed table-striped table-bordered">
      <thead>
      <tr>
        <th style="width: 13px;"><input type="checkbox" style="margin-top: 0;"/></th>
        <th><?php echo __('Revision ID'); ?></th>
      </tr>
      </thead>
      <tbody id="revision_body">
      <?php if (!empty($this->revisions) ) { ?>
        <?php foreach ($this->revisions as $revision) { ?>
          <?php
          $ran = $this->revision >= $revision;
          $class = array();
          if ($ran) {
            $class[] = 'ran';
          }

          $files = $this->_getRevisionFiles($revision);
          ?>
          <tr data-revision="<?php echo $revision; ?>"<?php echo count($class) ? ' class="' . implode(' ', $class) . '"' : ''; ?>>
            <td class="center">
              <input type="checkbox" name="revisions[]" value="<?php echo $revision; ?>"<?php echo $ran ? '' : ' checked="checked"'; ?> style="margin-top: 7px;"/>
            </td>
            <td>
              <h3 class="nomargin">
                <a href="javascript:" class="revision-handle"><?php echo $revision; ?></a>

                <div class="pull-right" style="margin-top:-32px;">
                  <button type="button" class="btn btn-mini btn-danger" data-role="delete-revision" data-revision="<?php echo $revision; ?>" style="margin-top: -1px;"><?php echo __('Delete directory') ?></button>
                </div>
              </h3>

              <div class="revision-files" style="display: none;">
                <?php if (count($files)) { ?>
                  <?php $i = 0; ?>
                  <?php foreach ($files as $file) { ?>
                    <?php
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    $content = htmlentities($this->_getRevisionFileContents($revision, $file), ENT_QUOTES, 'UTF-8');
                    $lines = substr_count($content, "\n");
                    ?>
                    <div id="revision-file-<?php echo $revision; ?>-<?php echo ++$i; ?>">
                      <div class="log"></div>
                      <div class="alert alert-info heading">
                        <button type="button" class="btn btn-mini btn-info pull-right" data-role="editor-save" data-revision="<?php echo $revision; ?>" data-file="<?php echo $file; ?>" style="margin-top: -1px;"><?php echo __('Save file') ?></button>
                        <button type="button" class="btn btn-mini btn-danger pull-right" data-role="editor-delete-file" data-revision="<?php echo $revision; ?>" data-file="<?php echo $file; ?>" style="margin-top: -1px; margin-right: 10px; "><?php echo __('Delete file') ?></button>
                        <strong class="alert-heading"><?php echo $file; ?></strong>
                      </div>
                      <textarea data-role="editor" name="revision_files[<?php echo $revision; ?>][<?php echo $file; ?>]" rows="<?php echo $lines + 1; ?>"><?php echo $content; ?></textarea>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
            </td>
          </tr>
        <?php } ?>
      <?php } ?>
      </tbody>
    </table>
    <input type="submit" class="span3 btn btn-primary" value="Run selected revisions"/>
    <button id="add_revision" class="span3 btn btn-success pull-right">New revision</button>
  </form>

<script type="text/javascript">
document.observe('dom:loaded', function () {
  var form = $('revisions');
  if (!form) {
    return;
  }

  function init_textarea(textarea) {
    textarea['data-editor'] = CodeMirror.fromTextArea(textarea, {
      mode: "text/x-mysql",
      tabMode: "indent",
      matchBrackets: true,
      autoClearEmptyLines: true,
      lineNumbers: true,
      theme: 'default'
    });
  }

  var textareas = form.select('textarea');
  textareas.each(init_textarea);

  function revision_handle(event) {

    var element = event.findElement('.revision-handle');
    var container = element.up('td').down('.revision-files');
    if (container) {
      container.toggle();
      if (!container.visible()) {
        return;
      }

      var textareas = container.select('textarea[data-role="editor"]');
      if (textareas) {
        textareas.each(function (textarea) {
          textarea['data-editor'].refresh();
        });
      }
    }
  }

  $$('.revision-handle').invoke('observe', 'click', revision_handle);

  function editor_save(event) {
    var self = this;

    var editor = this.up('.heading').next('textarea')['data-editor'];
    var container = this.up('[id^="revision-file"]');

    this.disable();

    clear_messages(container);

    new Ajax.Request('index.php?a=saveRevisionFile', {
      parameters: {
        revision: this.getAttribute('data-revision'),
        file: this.getAttribute('data-file'),
        content: editor.getValue()
      },
      onSuccess: function (transport) {
        self.enable();

        var response = transport.responseText.evalJSON();

        if (response.error) {
          return render_messages('error', container, response.error);
        }

        render_messages('success', container, response.message);
      }
    });
  }

  $$('button[data-role="editor-save"]').invoke('observe', 'click', editor_save);

  function editor_delete_file(event) {
    var self = this;
    var container = this.up('[id^="revision-file"]');

    this.disable();

    clear_messages(container);

    new Ajax.Request('index.php?a=deleteRevisionFile', {
      parameters: {
        revision: this.getAttribute('data-revision'),
        file: this.getAttribute('data-file')
      },
      onSuccess: function (transport) {
        self.enable();
        container.update('<div class="log"></div>');

        var response = transport.responseText.evalJSON();

        if (response.error) {
          return render_messages('error', container, response.error);
        }

        render_messages('success', container, response.message);
      }
    });
  }

  $$('button[data-role="editor-delete-file"]').invoke('observe', 'click', editor_delete_file);

  $$('button[data-role="editor-save"]').invoke('observe', 'click', editor_save);

  function delete_revision(event) {
    var self = this;
    var container = this.up('tr');

    this.disable();

    new Ajax.Request('index.php?a=deleteRevisionDirectory', {
      parameters: {
        revision: this.getAttribute('data-revision')
      },
      onSuccess: function (transport) {
        self.enable();
        clear_messages('revisions');
        var response = transport.responseText.evalJSON();

        if (response.error) {
          return render_messages('error', 'revisions', response.error);
        }

        render_messages('success', 'revisions', response.message);
        container.remove();
      }
    });
  }

  $$('button[data-role="delete-revision"]').invoke('observe', 'click', delete_revision);

  $$("#add_revision").invoke('observe', 'click', function (event) {
    event.stop();
    var self = this;
    this.disable();

    clear_messages('revisions');
    var schema_name = $j('#schema').find('input[name="schema[]"]:checked');
    if ( schema_name.length > 0 ){
      schema_name = schema_name[0].value + '.sql';
    }
    else {
      render_messages('error', 'revisions', "<?php echo __("You didn't select any schema object to add revision to. Please check one of the <strong>Schema object</strong> on the right"); ?>");
      Effect.ScrollTo('log', {duration: 0.2});
      self.enable();
      return false;
    }

    new Ajax.Request('index.php?a=addRevisionFolder', {
      parameters: {
        file : schema_name
      },
      onSuccess: function (transport) {
        self.enable();

        var response = transport.responseText.evalJSON();

        if (response.ok != true) {
          render_messages('error', 'revisions', response.message, '<?php echo __('The following errors occured:'); ?>');
        }
        else {
          render_messages('success', 'revisions', response.message, '<?php echo __('The following actions completed successfuly:'); ?>');


          var rev = parseInt(response.rev);
          if (!isNaN(rev)) {
            var tbody = document.getElementById('revision_body');
            var tr = tbody.insertRow(0);
            tr.setAttribute('data-revision', rev);

            var td = document.createElement('td');
            td.className = "center";
            td.innerHTML = '<input type="checkbox" name="revisions[]" value="' + rev + '" style="margin-top: 7px" />';

            var td2 = document.createElement('td');
            td2.innerHTML = '<h3 class="nomargin"><a href="javascript:" class="revision-handle">' + rev + '</a></h3><div class="pull-right" style="margin-top:-32px;">'
              + '<button type="button" class="btn btn-mini btn-danger" data-role="delete-revision" data-revision="' + rev + '" tyle="margin-top: -1px;"><?php echo __('Delete directory');?></button></div>'
              + '<div class="revision-files" style="display: none;"><div id="revision-file-' + rev + '-1">'
              + '<div class="log"></div>'
              + '<div class="alert alert-info heading">'
              + '<button data-role="editor-save" data-revision="' + rev + '" data-file="' + schema_name + '" type="button" class="btn btn-mini btn-info pull-right" style="margin-top: 1px;"><?php echo __('Save file');?></button>'
              + '<button type="button" class="btn btn-mini btn-danger pull-right" data-role="editor-delete-file" data-revision="' + rev + '" data-file="' + schema_name + '" style="margin-top: -1px; margin-right: 10px; "><?php echo __('Delete file');?></button>'
              + '<strong class="alert-heading">' + schema_name + '</strong>'
              + '</div>'
              + '<textarea data-role="editor" name="revision_files[' + rev + '][' + schema_name + ']" rows="1" style="display:none;"> </textarea>'
              + '</div></div>';
            tr.appendChild(td);
            tr.appendChild(td2);
            $$('.revision-handle').invoke('observe', 'click', revision_handle);
            $$('button[data-role="editor-save"]').invoke('observe', 'click', editor_save);
            $$('button[data-role="editor-delete-file"]').invoke('observe', 'click', editor_delete_file);
            $$('button[data-role="delete-revision"]').invoke('observe', 'click', delete_revision);
            textareas = form.select('textarea');
            init_textarea(textareas[0]);
          }
        }


        Effect.ScrollTo('log', {duration: 0.2});
      }
    })
  });

  form.on('submit', function (event) {
    event.stop();

    var data = form.serialize(true);

    clear_messages(this);

    if (!data.hasOwnProperty('revisions[]')) {
      render_messages('error', this, "<?php echo __("You didn't select any revisions to run") ?>");
      Effect.ScrollTo('log', {duration: 0.2});
      return false;
    }

    form.disable();

    new Ajax.Request('index.php?a=revisions', {
      parameters: {
        "revisions[]": data['revisions[]']
      },
      onSuccess: function (transport) {
        form.enable();

        var response = transport.responseText.evalJSON();

        if (typeof response.error != 'undefined') {
          return APP.growler.error('<?php echo _('Error!'); ?>', response.error);
        }

        if (response.messages.error) {
          render_messages('error', 'revisions', response.messages.error, '<?php echo __('The following errors occured:'); ?>');
        }

        if (response.messages.success) {
          render_messages('success', 'revisions', response.messages.success, '<?php echo __('The following actions completed successfuly:'); ?>');
        }

        var revision = parseInt(response.revision);
        if (!isNaN(revision)) {
          var rows = form.select('tr[data-revision]');

          rows.each(function (row) {
            row.removeClassName('ran');
            if (row.getAttribute('data-revision') > revision) {
              return;
            }
            row.addClassName('ran');
            row.down('.revision-files').hide();
            row.down('input[type="checkbox"]').checked = false;
          });
        }

        Effect.ScrollTo('log', {duration: 0.2});
      }
    });
  });
});
</script>