jQuery(function($) {

  if ($('#times-template').length > 0) {

    var timesSource = $("#times-template").html();
    var timesTemplate = Handlebars.compile(timesSource);

    var formSource = $('#form-template').html();
    var formTemplate = Handlebars.compile(formSource);

    var brickSource = $('#brick-template').html();
    var brickTemplate = Handlebars.compile(brickSource);

  }

  $('.sort-link').click(function(e) {
    e.preventDefault();
    var newVal = $(this).attr('href');
    newVal = newVal.substring(1, newVal.length);

    var $form = $('.search-form');
    $form.find('[name="sort"]').val(newVal);

    $form.submit();
  });

  function createBrick(key, val) {
    var el = $(brickTemplate({key: key, val: val}));

    return el;
  }

  function removeBrick(key, container) {
    container.find('[data-key="'+key+'"]').remove();
  }

  function selectize(element, brickContainer) {

    if (element.prop("tagName") !== 'SELECT' && element.is(':visible')) {
      return;
    }

    var newName = element.attr('name');
    if (newName.indexOf('[]') < 0) {
      newName += '[]';
    }
    element.attr('name', newName);

    var newElement;

    if (element.is(':hidden')) {
      // this means this has run already. i dont know wtf is going on with the events
      newElement = element.parent().find('.select-replacement');

    } else {
      element.hide();

      var theEnum = [];

      $.each(element.find('option'), function() {
        var key = $(this).val();
        var val = $(this).text();

        theEnum.push({ key: key, value: val });

      });

      var HTML = timesTemplate({ enum: theEnum });
      newElement = $(HTML);

      element.after(newElement);
    }

    newElement.find('.dropdown-toggle').off('click').on('click', function() {
      var trElement = $(this).closest('tr');
      var trElementWidth = trElement.outerWidth();

      var menu = trElement.find('.dropdown-menu-ul');
      if (menu.css('position') === 'static') {
        menu.width($(this).outerWidth() - 2);
      } else {
        var left = trElement.offset().left;
        var currentLeft = $(this).offset().left;

        // need to offset it

        var newLeft = (currentLeft - left) * -1;

        menu.width(trElementWidth - 2).css('left', newLeft);
      }

    });

    newElement.find('.select-replacement-all').off('click').on('click', function(e) {
      e.stopPropagation();

      var par = $(this).parent().parent().find('li').addClass('active');
      var options = element.find('option');

      options.each(function() {
        var theOption = $(this);
        theOption.attr('selected', true);
        if (theOption.attr('selected') !== true) {
          var key = theOption.val();
          var brick = createBrick(theOption.val(), theOption.text());

          brickContainer.append(brick);

          brick.find('a').on('click', function() {
            removeBrick(key, brickContainer);
            $thisElement.click();
          });

        }
      });

    });

    newElement.find('.select-replacement-none').off('click').on('click', function(e) {
      e.stopPropagation();
      $(this).parent().parent().find('li').removeClass('active'); // ('.dropdown-menu')
      var options = element.find('option');

      options.each(function() {
        removeBrick($(this).val(), brickContainer);
      });

      options.attr('selected', false);
    });

    newElement.find('.select-replacement-close').off('click').on('click', function(e) {
      e.stopPropagation();
      $(this).parents('.btn-group').removeClass('open');
    });

    newElement.find('.select-option').off('click').on('click', function(e) {
      e.stopPropagation();

      var $thisElement = $(this);

      var key = $thisElement.data('key');

      var isActive = $thisElement.parent().hasClass('active');
      $thisElement.parent().toggleClass('active');

      var opt = element.find('option[value="' + key + '"]');

      if (isActive) {
        opt.attr('selected', false);
        removeBrick(key, brickContainer);
      } else {
        opt.attr('selected', true);
        var brick = createBrick(key, opt.text());
        brick.find('a').on('click', function() {
          removeBrick(key, brickContainer);
          $thisElement.click();
        });
        brickContainer.append(brick);
      }

    });

  }

  /*
  $(document).on('change', '.btn-file :file', function() {
    var input = $(this),
        numFiles = input.get(0).files ? input.get(0).files.length : 1,
        label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
    input.trigger('fileselect', [numFiles, label]);
  });

  $(document).ready( function() {
    $('.btn-file :file').on('fileselect', function(event, numFiles, label) {
        console.log(numFiles);
        console.log(label);
    });
  });
  */

  var Autoadd = function() {
    this.self = $('.autoadd');
    this.schemae = {};

    this.rows = [];

    this.row = -1;
    this.form = null;

    this.postExecutionHooks = {};
    this.executionCache = {};

  };

  Autoadd.prototype.addToExecutionCache = function(index, html) {
    this.executionCache[index] = html;
  };

  Autoadd.prototype.getFromExecutionCache = function(index) {
    return this.executionCache[index] || false;
  };

  Autoadd.prototype.getRowCounter = function() {
    return this.row;
  };

  Autoadd.prototype.incrementRow = function() {
    return this.row++;
  };

  Autoadd.prototype.resetRow = function() {
    var $this = this;
    this.row = -1;

    $('.hook-generated').each(function() {
      var index = $(this).data('index');

      $this.addToExecutionCache(index, $(this).html());
    });
  };

  Autoadd.prototype.serialize = function() {
    var form = this.form || this.self.parents('form');
    return form.serializeArray();
  };

  Autoadd.prototype.isDirty = function() {
    var $formData = this.serialize();

    var isValid = $formData.filter(function(v) {
      return v.value.toString().length < 1;
    });

    isValid = isValid.length < 1;

    if (!isValid) {
      alert('Please fill out the previous fields first');
    }

    return !isValid;
  };

  Autoadd.prototype.addSchema = function(label, schema) {
    this.schemae[label] = schema;
  };

  Autoadd.prototype.createInputFor = function(label, schemaData, schemaName, obj) {

    var input;
    var type;

    if (schemaData.type === undefined) {
      this.postExecutionHooks[this.getRowCounter()] = {
        type: label,
        data: schemaData,
        callback: schemaData.callback
      };

      return;
    } else if (schemaData.enum) {
      input = $('<select></select>');
      input.attr('multiple', true).attr('data-placeholder', 'Times');

      for (var x in schemaData.enum) {
        var option = schemaData.enum[x];

        var val = Object.keys(option)[0];

        var theOption = $('<option></option>').text(option[val]).attr('value', val);
        input.append(theOption);

      }

    } else if (schemaData.type === Text) {
      input = $('<textarea></textarea>');
    } else {

      input = $('<input>');

      switch (schemaData.type || schemaData) {
        case Number:
          type = 'text';
          break;
        default:
          type = 'text';
      }

      input.attr('type', type);
    }

    input.attr('name', label + '[' + this.getRowCounter() + '][' + schemaName + ']')
      .addClass('form-control').attr('value', schemaData.default || '')
      .attr('placeholder', schemaData.label || schemaName)
      .attr('required', true).addClass(label + '-form-input');

    if (schemaData.disabled || false) {
      input.attr('disabled', true);
    }

    var finalElement;

    if (typeof(obj) === 'object') {
      var key = Object.keys(obj)[0];
      var value = obj[key];

      console.log(key);
      console.log(value);

      input.attr('type', 'hidden');
      input.val(key);

      var newElement = $('<span></span>');
      newElement.append(input);
      newElement.append($('<span></span>').text(value));

      finalElement = newElement;
    } else {

      if (obj) {
        input.val(obj);
      }

      if (schemaData.type === Date) {

        var wrapper = $('<div></div>').addClass('input-group');
        var addon = $('<span class="input-group-addon"><i class="fa fa-calendar fa-lg"></i></span>');

        input.attr('id', 'DayField');
        wrapper.append(input).append(addon);

        input.datepicker();

        finalElement = wrapper;
      } else {
        finalElement = input;
      }

    }

    var labelString = schemaData.label || schemaName;

    var td = $('<td></td>');
    td.attr('title', labelString.toUpperCase());
    td.html(finalElement);

    return td;
  };

  Autoadd.prototype.pushRow = function(label, data) {
    var schema = this.schemae[label] || false;
    if (!schema) return false;

    this.incrementRow();

    var newObject = [];

    var first = true;

    for (var x in schema) {
      var theData = data[x] || '';
      var input = this.createInputFor(label, schema[x], x, theData);

      if (input) {
        if (first) {
          input.append(' <a href="javascript: void(0);" class="autoadd-row-remove"><i class="fa fa-times"></i></a>');
        }
        newObject.push(input);
      }
      first = false;
    }

    this.rows.push(newObject);

    return newObject;

  };

  Autoadd.prototype.adjustPECacheForRemoval = function(number) {
    delete this.postExecutionHooks[number]; // deletes it

    var newPECache = {};
    var newHooks = {};
    var newIndex;

    for (var x in this.postExecutionHooks) {
      if (x > number) {
        newIndex = x - 1;
      } else {
        newIndex = x;
      }

      newHooks[newIndex] = this.postExecutionHooks[x];
    }

    for (var y in this.executionCache) {
      if (y > number) {
        newIndex = y - 1;
      } else {
        newIndex = y;
      }

      newPECache[newIndex] = this.executionCache[y];
    }

    this.postExecutionHooks = newHooks;
    this.executionCache = newPECache;

  };

  Autoadd.prototype.postExecutionHookFor = function(number, newRow) {
    var hook = this.postExecutionHooks[number] || false;

    if (!hook) {
      return;
    }

    var tr = $('<tr></tr>');
    tr.addClass('hook-generated').attr('data-index', number);

    var cached = this.getFromExecutionCache(number);

    if (cached) {
      tr.html(cached);

      return tr;
    }

    this.postExecutionHooks[number].executed = true;

    hook.callback(hook.type, hook.data, number, function(data) {
      tr.html(data);
    });

    return tr;

  };

  Autoadd.prototype.popRow = function(index) {
    this.adjustPECacheForRemoval(index);

    var rows = this.rows;
    var newRows = [];

    for (var x in rows) {
      if (x == index) {
        continue;
      }
      newRows.push(rows[x]);
    }


    this.rows = newRows;
    return this;
  };

  Autoadd.prototype.rebindEvents = function() {
    var rowRemove = this.self.find('.autoadd-row-remove');
    var $this = this;

    rowRemove.off('click').on('click', function() {

      var thisRow = $(this).parents('tr');
      var index = thisRow.data('index');

      $this.popRow(index);

      $this.sync();
    });

    $('.checkbox-button').off('click').on('click', function() {
      var $this = $(this);
      if ($this.is(':checked')) {
        $this.parent().addClass('active');
      } else {
        $this.parent().removeClass('active');
      }
    });

    if ($('.recurrence-radio').length > 0) {

      $('.recurrence-radio').off('change').on('change', function() {
        var parentWrapper = $(this).parents('td');

        if (!$(this).is(':checked')) {
          return;
        }

        var val = $(this).val();


        if (val === 'weekly') {
          parentWrapper.find('.repeat-subform').show();
        } else {
          parentWrapper.find('.repeat-subform').hide();
        }
      });

      $('select[multiple="multiple"].offer-form-input').each(function() {
        var b = $(this).parents('tr').next().find('.brick-container');
        selectize($(this), b);
      });

      $('.recurrence-radio[value="weekly"]').click();

    }


  };

  Autoadd.prototype.sync = function() {

    this.resetRow();

    var newHtml = [];
    for (var x in this.rows) {

      this.incrementRow();

      var theRow = this.rows[x];

      var newRow = $('<tr></tr>');
      newRow.attr('data-index', x);
      newRow.addClass('autoadd-added');

      for (var rt in theRow) {
        newRow.append(theRow[rt]);
      }

      newHtml.push(newRow);

      var maybeHtml = this.postExecutionHookFor(x, newRow);

      if (maybeHtml) {
        newHtml.push(maybeHtml);
      }

    }

    this.self.html('');
    for (var y in newHtml) {
      this.self.append(newHtml[y]);
    }

    this.rebindEvents();

  };

  Autoadd.prototype.bindTo = function(form) {
    this.form = $(form);

    this.form.on('submit', function(e) {

      this.form.find('.alert').remove();

      // Need to iterate through all the rows and validate them against their schema
      var data = this.form.serializeArray();
      var errors = [];

      for (var itemNumber in data) {
        var theItem = data[itemNumber];

        var key = theItem.name;
        var value = theItem.value;

        // Need to parse the key
        // first is gonna be autoadd since we're using the autoadd form
        var found = key.match(/([^[]+)\[([0-9]+)\]\[(.+)\]/);

        var schemaType = found[1] || false;
        var theNumber = found[2] || false;
        var itemCategory = found[3] || false;

        if (!this.schemae.hasOwnProperty(schemaType) || !itemNumber ||!itemCategory) {
          continue;
        }

        var theSchema = this.schemae[schemaType];
        var relatedSchema = theSchema[itemCategory] || false;

        if (!relatedSchema) {
          continue;
        }

        if (relatedSchema.validation) {
          var valid = relatedSchema.validation(value);

          if (valid === true) {
            continue;
          } else {
            errors.push(valid);
          }
        }

      }

      if (errors.length > 0) {
        var ul = $('<div class="alert alert-danger"><ul></ul></div>');

        for (var x in errors) {
          var li = $('<li></li>').text(errors[x]);
          ul.append(li);
        }

        this.form.prepend(ul);
        return false;
      } else {
        return true;
      }


    }.bind(this));

  };

  var theAutoadd = new Autoadd();

  theAutoadd.addSchema('treatment', {
    treatmentCategory: { type: String, label: 'Treatment' },
    name: { type: String, label: 'What is it?' },
    description: { type: Text, label: 'What do they get?', },
    duration: { type: Number, label: 'Duration (in minutes)', validation: function(val) {
      var intVal = parseInt(val);

      if (intVal >= 520) {
        return 'Duration must be shorter than 520 minutes';
      }

      if (intVal >= 15) {
        return true;
      }

      return 'Duration must be longer than 15 minutes';

    } },
    originalPrice: { type: Number, label: 'Full Price', default: 0.0,
      validation: function(val) {
        var floatVal = parseFloat(val);

        if (floatVal > 0.00) {
          return true;
        }

        return 'Price must be greater than $0.00';

      }
    },
  });
  var enumPopulated = [];
  for (var h = 7; h <= 21; h++) {
    for (var m = 0; m < 60; m = m + 15) {
      var mFormatted;
      if (m === 0) {
        mFormatted = '00';
      } else {
        mFormatted = m;
      }

      var nicehour;

      if (h === 0 || h === 12) {
        nicehour = h;
      } else {
        nicehour = h % 12;
      }

      var meridian = h >= 12 ? 'PM' : 'AM';
      var string = nicehour + ':' + mFormatted + ' ' + meridian;

      var obj = {};
      obj[h + ':' + mFormatted] = string;

      enumPopulated.push(obj);
    }
  }

  theAutoadd.addSchema('offer', {
    treatmentCategory: { type: String, label: 'Treatment' },
    startDate: { type: Date, label: 'Start Date' },
    times: { type: String, label: 'Times', enum: enumPopulated },
    originalPrice: { type: Number, label: 'Original Price', disabled: true },
    discountPrice: { type: Number, label: 'Discount Price', default: 0.0 },
    recurrence: { type: undefined, callback: function(schemaType, schemaData, num, callback) {
      var x = $(formTemplate({
        prefix: schemaType,
        index: num
      }));

      callback(x);

      return x;

    }}
  });

  theAutoadd.bindTo('#AutoaddForm');

  function getAutoadd() {
    return theAutoadd;
  }

  $('.canvas').click(function() {
    // close form-focused
    $('.form-focused').removeClass('form-focused');
  });

  $('.panel-sidebar').click(function(e) {
    e.stopPropagation();
  });

  $('.ul-filter')
    .on('focus', function() {
      $(this).parents('.panel').first().addClass('form-focused');
    });

  $('.ul-filter').on('keyup', function() {
    var $this = $(this);
    var filters;

    if ($this.data('filters')) {
      filters = $this.data('filters');
    } else {
      return false;
    }

    var grep = $(filters);

    if (!grep) {
      return false;
    }

    var value = $this.val().toLowerCase();

    grep.children('li').each(function() {
      var parentKey = $(this).data('parent');

      if (
        ($(this).text().toLowerCase().search(value) > -1) ||
        (parentKey && parentKey.search(value) > -1)
      ) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });

  });

  $('.add-button-autoadd').click(function() {
    var $this = $(this);
    var a = getAutoadd();

    /*
    if (a.isDirty()) {
      return false;
    }
    */

    var id = $this.data('add-id');
    var obj = {};
    obj[id] = $this.data('add-label');

    if ($(this).hasClass('button-treatment')) {
      a.pushRow('treatment', {
        treatmentCategory: obj
      });
    } else {
      a.pushRow('offer', {
        treatmentCategory: obj,
        originalPrice: $this.data('original-price')
      });
    }

    a.sync();


  });

  $('.checkbox-button').on('change', function() {
    var $this = $(this);
    if ($this.is(':checked')) {
      $this.parent().addClass('active');
    } else {
      $this.parent().removeClass('active');
    }
  });

  $('[data-confirm="true"]').click(function(e) {
    if (!confirm('Are you sure you want to delete this business?')) {
      e.preventDefault();
      return false;
    }
  });

  $('input[type="file"]').on('change', function() {
    // check if parent is a form group
    var parent = $(this).parent();
    if (!parent.hasClass('form-group')) {
      return;
    }


    var val = $(this).val();
    if (val === '') {
      parent.removeClass('has-attachment');
    } else {
      parent.addClass('has-attachment');
    }

  });

});
