<?php
	// NOTE: Due to the use of "Eval", DataGrid is currently being redone to not use EVAL.
	// A new datagrid control will be released in Beta 2 of the framework, which will have a much
	// more secure and robust render handler.  Naturally, generated ListForms will be updated, accordingly,
	// to work with the new, improved DataGrid class.

	// DataGrid control is used to display tabular information (e.g. lists)
	//
	// The control itself will display things based off of an array of objects that gets set as the "Data Source".
	// It is particularly useful when combined with the Class::LoadArrayByXXX() functions or the Class::LoadAll()
	// that is generated by the CodeGen framework, or when combined with custom Class ArrayLoaders that you define
	// youself, but who's structure is based off of the CodeGen framework.
	//
	// The DataGrid essentially is a <table>. For each item in a datasource's Array, a row (<tr>) will be generated.
	// You can define any number of DataGridColumns which will result in a <td> for each row.
	// Within the DataGridColumn, you can specify the DataGridColumn::Html that should be displayed.
	//
	// The HTML looks for the special back-tick character "`", and will do PHP Eval's on antyhing within a pair of ` `.
	// Moreover, the special variable $_ROW can be used to access the actual contents of that particular row's data
	// in the main data source array.
	//
	// So, for example, supposed the following:
	//		$strSimpleArray = {"Blah", "Foo", "Baz", "Fun"};
	//		$dtgDataGrid = new DataGrid("dtgDataGrid");
	//		$dtgDataGrid->AddColumn("Column Heading", "Contents: `$_ROW`");
	//		$dtgDataGrid->DataSource = $strSimpleArray;
	// This will generate a simple 4-row, 1-column table that contains the following:
	//      Column Heading
	//      --------------
	//      Contents: Blah
	//      Contents: Foo
	//      Contents: Baz
	//      Contents: Fun
	// In this case, $_ROW is a string, itself, which is each item in the DataSource's array.
	//
	// Utilizing the back-tick and $_ROW feature, you can do infinitely more complicatd display functionality:
	//		$dtgDataGrid = new DataGrid("dtgDataGrid");
	//		$dtgDataGrid->AddColumn("Title", "<b>`$_ROW->Title`</b>");
	//		$dtgDataGrid->AddColumn("Calculated Result", "`DisplayResults($_ROW->Calculate())`");
	//		$dtgDataGrid->DataSource = Foo::LoadAll();
	// This could then generate a table with much more data-rich information:
	//      Title             Calculated Result
	//      ----------------- --------------------
	//      Some Title Here   $28,298.24
	//      Foo Baz Bar       $18,000.00
	//      Blah              (None)
	// In this case, $_ROW is actually a Foo object.
	//
	//
	// IMPORTANT: Please note that while all properties can/should be set up only once within the form's
	// !IsPostBack() clause, the DataSource **MUST** be set **EVERY TIME**.  The contents of DataSource
	// do NOT persist from postback to postback.
	//
	//
	// The appearance of the datagrid control appears to be complicated, but keep in mind that it simply
	// utlizes the cascading nature of how browsers render tables based on styles assigned to
	// the <table>, <tr>, and <td>.  In short:
	//     Appearance properties defined to the DataGrid, itself, show up as HTML Attributes
	//     and CSS Styles within the <table> tag.
	//
	//     Appearance properties defined to a specific row's DataGridRowStyle will show up as
	//     HTML attributes within that specific row's <tr> tag.
	//
	//     Appearance properties defined to a DataGridColumn will show up as HTML attributes
	//     within that specific row's <td> tag.
	//
	// So, attributes will only show up if it is defined at that particular level.  So if you define a background color
	// for a DataGridRowStyle for a particular row, but not for a DataGridColumn or for the DataGrid in general, that
	// background style will only show up in that row.
	//
	// And due to the cascaiding nature of how browsers render tables, any undefined appearance property will simply
	// inherit from the parent (where a <td>'s parent is the <tr>, and the <tr>'s parent is the <table>,
	// and any defined appearance property will simply override whatever was defined by the parent.
	// 
	//
	// Sorting
	// Whether or not a column can be sorted depends on whether or not you define a SortByCommand (and
	// subsequently a ReverseSortByComamnd) on the DataGridColumn itself.  This SortByCommand is meant
	// to be the SQL syntax used in an "ORDER BY" clause of a SQL query.  This fits in really well
	// with the CodeGen Framework's Class::LoadArrayByXXX() and Class::LoadAll() which takes "$strSortInfo"
	// as an optional parameter.
	// 
	// If a DataGrid is being sorted by a specific column, DataGrid::SortInfo will return to you the contents
	// of DataGridColumn::SortByCommand (or ReverseSortByCommand if it's a reverse sort) for the specific
	// column being sorted by.  Therefore, you can set up your data source like:
	//     $dtgDataGrid->DataSource = Foo::LoadAll($dtgDataGrid->SortInfo);
	//
	//
	// Pagination
	// Pagination can be toggled on and off with the DataGrid::Paginate flag.  When enabling pagination, you
	// must specify how many items, TOTAL, are in the full list (DataGrid::TotalItemCount).  The DataGrid will
	// then automatically calculate the SQL Limit information (as used in a "LIMIT" clause of a SQL query) to
	// be used when querying a specific subset of the total list.  As with sorting, this fits really well
	// with the CodeGen Framework's LoadArray methods which takes $strLimitInfo" as an optional parameter.
	//
	// Moreover, the codegen also auto-generates CountBy methods for every LoadAll/LoadArray method it generates
	// to assist with the DataGrid::TotalItemCount property:
	//     $dtgDataGrid->TotalItemCount = Foo::CountAll();
	//     $dtgDataGrid->DataSource = Foo::LoadAll($dtgDataGrid->SortInfo, $dtgDataGrid->LimitInfo);
	//
	//
	// Appearance-related properties:
	// * "AlternateRowStyle" is the DataGridRowStyle object that defines how "alternating rows" should be displayed
	// * "BorderCollapse" defines the BorderCollapse css style for the table
	// * "HeaderLinkStyle" is the DataGridRowStyle object that defines how links, specifically, in the header row 
	//   should be displayed.  Basically, anything defined here will show up as html attributes and css styles within the
	//   '<a href="">' tag of the link, itself, in the header.  Links in the header ONLY GET DISPLAYED when a column is
	//   sortable
	// * "HeaderRowStyle" is the DataGridRowStyle object that defines how the "header row" should
	//    be displayed (attributes that get rendred in the header row's <tr>)
	// * "RowStyle" is the main or "default" DataGridRowStyle for the entire table.  Any overriding row style
	//   (see "OverrideRowStyle(int, DataGridRowStyle)" below) or any appearance properties
	//   set in AlternateRowStyle or HeaderRowStyle will be applied in those specific situations.
	//   Any appearance properties NOT set in ovverrides, alternate, or header will simply
	//   default to what RowStyle has defined.
	// * "Noun" is the word that shows up when "Paginate" is set to true when it reports "Result: 1 item found."
	// * "NounPlural" is the word that shows up when "Paginate" is set to true when it reports "Result: 1 items found."
	//
	// Due to a bug with PHP, you cannot set a property of a property.  DataGrid's AlternateRowStyle, HeaderRowStyle and RowStyle
	// are obviously instances of DataGridRowStyle objects which have properties in and of themselves.
	// So unfortuantely, the following code will **NOT** work:
	//     $dtgDataGrid->RowStyle->BackColor = "blue";
	// Instead, you will need to do the following:
	//     $objRowStyle = $dtgDataGrid->RowStyle;
	//     $objRowStyle->BackColor = "blue";
	//
	// Behavior-related properties:
	// * "Paginate" is whether or not you want pagination.
	// * "ItemsPerPage" is how many items you want to display per page when Pagination is enabled.
	// * "PageNumber" is the current page number you are viewing
	// * "TotalItemCount" is the total number of items in the ENTIRE recordset -- only used when Pagination is enabled
	// * "CurrentRowIndex" (READONLY) is the current row index that is being rendered.  Useful for render-helper functions
	//   that may get called when rendering the datagrid, itself
	// * "SortColumnIndex" is the current column that is being "sorted by" (or -1 if none)
	// * "SortDirection" specifies the direction of that sort, 0 for SortBy, and 1 for ReverseSortBy
	//
	// Layout-related properties:
	// * "CellPadding" refers the the HTML CellPadding attribute of the <table> 
	// * "CellSpacing" refers the the HTML CellSpacing attribute of the <table> 
	// * "GridLines" refers the the HTML rules attribute of the <table>
	// * "ShowHeader" is the flag of whether or not to show the Header row
	//
	// Misc properties:
	// * "DataSource" is an array of anything.  THIS MUST BE SET EVERY TIME (DataSource does NOT persist from
	//   postback to postback
	// * "LimitInfo" (readonly) is what should be passed in to the LIMIT clause of the sql query that retrieves
	//   the array of items from the database
	// * "SortInfo" (readonly) is what should be passed in to the SORT BY clause of the sql query that retrieves
	//   the array of items from the database


	// Due to the fact that DataGrid's will perform php eval's on anything that is back-ticked within each column/row's 
	// DataGridColumn::HTML, we need to set up this special DataGridEvalHandleError error handler to correctly report
	// errors that happen.
	function DataGridEvalHandleError($__exc_errno, $__exc_errstr, $__exc_errfile, $__exc_errline) {
		$__exc_objBacktrace = debug_backtrace();
		for ($__exc_intIndex = 0; $__exc_intIndex < count($__exc_objBacktrace); $__exc_intIndex++) {
			$__exc_objItem = $__exc_objBacktrace[$__exc_intIndex];

			if ((strpos($__exc_errfile, "DataGrid.inc") !== false) &&
				(strpos($__exc_objItem["file"], "DataGrid.inc") === false)) {
				$__exc_errfile = $__exc_objItem["file"];
				$__exc_errline = $__exc_objItem["line"];
			} else if ((strpos($__exc_errfile, "Form.inc") !== false) &&
				(strpos($__exc_objItem["file"], "Form.inc") === false)) {
				$__exc_errfile = $__exc_objItem["file"];
				$__exc_errline = $__exc_objItem["line"];
			}
		}

		global $__exc_dtg_errstr;
		if (isset($__exc_dtg_errstr) && ($__exc_dtg_errstr))
			$__exc_errstr = sprintf("%s\n%s", $__exc_dtg_errstr, $__exc_errstr);
		QcodoHandleError($__exc_errno, $__exc_errstr, $__exc_errfile, $__exc_errline);
	}

	abstract class QDataGridBase extends QPaginatedControl {
		// APPEARANCE
		protected $objAlternateRowStyle = null;
		protected $strBorderCollapse = QBorderCollapse::NotSet;
		protected $objHeaderRowStyle = null;
		protected $objOverrideRowStyleArray = null;
		protected $objHeaderLinkStyle = null;
		protected $objRowStyle = null;

		// LAYOUT
		protected $intCellPadding = -1;
		protected $intCellSpacing = -1;
		protected $strGridLines = QGridLines::None;
		protected $blnShowHeader = true;
		protected $blnShowFooter = false;

		// MISC
		protected $objColumnArray;

		protected $intCurrentRowIndex;
		protected $intSortColumnIndex = -1;
		protected $intSortDirection = 0;
		
		protected $strLabelForNoneFound;
		protected $strLabelForOneFound;
		protected $strLabelForMultipleFound;
		protected $strLabelForPaginated;

		public function __construct($objParentObject, $strControlId = null) {
			try {
				parent::__construct($objParentObject, $strControlId);
			} catch (QCallerException  $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			$this->objRowStyle = new QDataGridRowStyle();
			$this->objAlternateRowStyle = new QDataGridRowStyle();
			$this->objHeaderRowStyle = new QDataGridRowStyle();
			$this->objHeaderLinkStyle = new QDataGridRowStyle();

			// Labels
			$this->strLabelForNoneFound = QApplication::Translate('<b>Results:</b> No %s found.');
			$this->strLabelForOneFound = QApplication::Translate('<b>Results:</b> 1 %s found.');
			$this->strLabelForMultipleFound = QApplication::Translate('<b>Results:</b> %s %s found.');
			$this->strLabelForPaginated = QApplication::Translate('<b>Results:</b>&nbsp;Viewing&nbsp;%s&nbsp;%s-%s&nbsp;of&nbsp;%s.');
			
			$this->objColumnArray = array();

			// Setup Sorting Events
			if ($this->blnUseAjax)
				$this->AddAction(new QClickEvent(), new QAjaxControlAction($this, 'Sort_Click'));
			else
				$this->AddAction(new QClickEvent(), new QServerControlAction($this, 'Sort_Click'));

			$this->AddAction(new QClickEvent(), new QTerminateAction());
		}

		// Used to add a DataGridColumn to this DataGrid
		public function AddColumn(QDataGridColumn $objColumn) {
			$this->blnModified = true;
			array_push($this->objColumnArray, $objColumn);
//			$this->objColumnArray[count($this->objColumnArray)] = $objColumn;
		}
		
		public function AddColumnAt($intColumnIndex, QDataGridColumn $objColumn) {
			$this->blnModified = true;
			try {
				$intColumnIndex = QType::Cast($intColumnIndex, QType::Integer);
			} catch (QInvalidCastException $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			if (($intColumnIndex < 0) ||
				($intColumnIndex > (count($this->objColumnArray))))
				throw new QIndexOutOfRangeException($intColumnIndex, "AddColumnAt()");

			if ($intColumnIndex == 0) {
				$this->objColumnArray = array_merge(array($objColumn), $this->objColumnArray);
			} else {
				$this->objColumnArray = array_merge(array_slice($this->objColumnArray, 0, $intColumnIndex),
					array($objColumn),
					array_slice($this->objColumnArray, $intColumnIndex));
			}
		}

		public function RemoveColumn($intColumnIndex) {
			$this->blnModified = true;
			try {
				$intColumnIndex = QType::Cast($intColumnIndex, QType::Integer);
			} catch (QInvalidCastException $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			if (($intColumnIndex < 0) ||
				($intColumnIndex > (count($this->objColumnArray) - 1)))
				throw new QIndexOutOfRangeException($intColumnIndex, "RemoveColumn()");

			array_splice($this->objColumnArray, $intColumnIndex, 1);
		}

		public function RemoveColumnByName($strName) {
			$this->blnModified = true;
			for ($intIndex = 0; $intIndex < count($this->objColumnArray); $intIndex++)
				if ($this->objColumnArray[$intIndex]->Name == $strName) {
					array_splice($this->objColumnArray, $intIndex, 1);
					return;
				}
		}

		public function RemoveColumnsByName($strName) {
			$this->blnModified = true;
			for ($intIndex = 0; $intIndex < count($this->objColumnArray); $intIndex++)
				if ($this->objColumnArray[$intIndex]->Name == $strName) {
					array_splice($this->objColumnArray, $intIndex, 1);
					$intIndex--;
				}
		}

		public function RemoveAllColumns() {
			$this->blnModified = true;
			$this->objColumnArray = array();
		}

		public function GetColumn($intColumnIndex) {
			if (array_key_exists($intColumnIndex, $this->objColumnArray))
				return $this->objColumnArray[$intColumnIndex];
		}

		public function GetColumnByName($strName) {
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn)
				if ($objColumn->Name == $strName)
					return $objColumn;
		}

		public function GetColumnsByName($strName) {
			$objColumnArrayToReturn = array();
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn)
				if ($objColumn->Name == $strName)
					array_push($objColumnArrayToReturn, $objColumn);
			return $objColumnArrayToReturn;
		}

		// If you want to override a SPECIFIC row's style, you can specify
		// the RowIndex and the DataGridRowStyle with which to override		
		public function OverrideRowStyle($intRowIndex, $objStyle) {
			try {
				$objStyle = QType::Cast($objStyle, "QDataGridRowStyle");
			} catch (QInvalidCastException $objExc) {
				$objExc->IncrementOffset();
				throw $objExc;
			}
			$this->objOverrideRowStyleArray[$intRowIndex] = $objStyle;
		}

		// Used upon rendering to find backticks and perform PHP eval's
		protected function ParseColumnHtml($objColumn, $objObject) {
			$_ITEM = $objObject;
			$_FORM = $this->objForm;
			$_CONTROL = $this;
			$_COLUMN = $objColumn;

			$strHtml = $objColumn->Html;
			$intPosition = 0;
			
			while (($intStartPosition = strpos($strHtml, '<?=', $intPosition)) !== false) {
				$intEndPosition = strpos($strHtml, '?>', $intStartPosition);
				if ($intEndPosition === false)
					return $strHtml;
				$strToken = substr($strHtml, $intStartPosition + 3, ($intEndPosition - $intStartPosition) - 3);
				$strToken = trim($strToken);
				
				if ($strToken) {
					// Because Eval doesn't utilize exception management, we need to do hack thru the PHP Error Handler
					set_error_handler("DataGridEvalHandleError");
					global $__exc_dtg_errstr;
					$__exc_dtg_errstr = sprintf("Incorrectly formatted DataGridColumn HTML in %s: %s", $this->strControlId, $strHtml);

					try {
						$strEvaledToken = eval(sprintf('return %s;', $strToken));
					} catch (QCallerException $objExc) {
						$objExc->DecrementOffset();
						throw $objExc;
					}

					// Restore the original error handler
					set_error_handler("QcodoHandleError");
					$__exc_dtg_errstr = null;
					unset($__exc_dtg_errstr);
				} else {
					$strEvaledToken = '';
				}

				$strHtml = sprintf("%s%s%s",
					substr($strHtml, 0, $intStartPosition),
					$strEvaledToken,
					substr($strHtml, $intEndPosition + 2));

				$intPosition = $intStartPosition + strlen($strEvaledToken);
			}

			return $strHtml;
		}

		// The Table, itself, should have no actions defined on it and should not be parsing anything
		public function ParsePostData() {}

		public function GetAttributes($blnIncludeCustom = true, $blnIncludeAction = false) {
			$strToReturn = parent::GetAttributes($blnIncludeCustom, $blnIncludeAction);

			if ($this->strGridLines == QGridLines::Horizontal)
				$strToReturn .= 'rules="rows" ';
			else if ($this->strGridLines == QGridLines::Vertical)
				$strToReturn .= 'rules="cols" ';
			else if ($this->strGridLines == QGridLines::Both)
				$strToReturn .= 'rules="all" ';

			if ($this->intCellPadding >= 0)
				$strToReturn .= sprintf('cellpadding="%s" ', $this->intCellPadding);

			if ($this->intCellSpacing >= 0)
				$strToReturn .= sprintf('cellspacing="%s" ', $this->intCellSpacing);

			$strBorder = $this->strBorderWidth;
			settype($strBorder, QType::Integer);
			$strToReturn .= sprintf('border="%s" ', $strBorder);

			if ($this->strBorderColor)
				$strToReturn .= sprintf('bordercolor="%s" ', $this->strBorderColor);

			return $strToReturn;
		}
		
		public function GetJavaScriptAction() {
			return "onclick";
		}

		public function GetStyleAttributes() {
			$strToReturn = parent::GetStyleAttributes();

			if ($this->strBorderCollapse == QBorderCollapse::Collapse) 
				$strToReturn .= 'border-collapse:collapse;';
			else if ($this->strBorderCollapse == QBorderCollapse::Separate) 
				$strToReturn .= 'border-collapse:separate;';

			return $strToReturn;
		}

		// Parse the _POST to see if the user is requesting a change in the sort column or page
		public function Sort_Click($strFormId, $strControlId, $strParameter) {
			$this->blnModified = true;

			if (strlen($strParameter)) {
				// Sorting
				$intColumnIndex = QType::Cast($strParameter, QType::Integer);
				$objColumn = $this->objColumnArray[$intColumnIndex];
				
				// First, reset pagination (if applicable)
				if ($this->objPaginator)
					$this->PageNumber = 1;

				// First, make sure the Column is Sortable
				if ($objColumn->OrderByClause) {
					// It is
					
					// Are we currently sorting by this column?
					if ($this->intSortColumnIndex == $intColumnIndex) {
						// Yes we are currently sorting by this column
						
						// In Reverse?
						if ($this->intSortDirection == 1) {
							// Yep -- unreverse the sort
							$this->intSortDirection = 0;
						} else {
							// Nope -- can we reverse?
							if ($objColumn->ReverseOrderByClause)
								$this->intSortDirection = 1;
						}
					} else {
						// Nope -- so let's set it to this column
						$this->intSortColumnIndex = $intColumnIndex;
						$this->intSortDirection = 0;
					}
				} else {
					// It isn't -- clear all sort properties
					$this->intSortDirection = 0;
					$this->intSortColumnIndex = -1;
				}
			}
		}

		protected function GetPaginatorRowHtml($objPaginator) {
			$strToReturn = sprintf('<tr><td colspan="%s" style="padding:4px 0px 4px 0px;"><table cellspacing="0" cellpadding="0" border="0" style="width:100%%;"><tr><td valign="bottom" style="width:50%%;font-size:10px;">', count($this->objColumnArray));

			if ($this->TotalItemCount > 0) {
				$intStart = (($this->PageNumber - 1) * $this->ItemsPerPage) + 1;
				$intEnd = $intStart + count($this->DataSource) - 1;
				$strToReturn .= sprintf($this->strLabelForPaginated,
					$this->strNounPlural,
					$intStart,
					$intEnd,
					$this->TotalItemCount);
			} else {
				$intCount = count($this->objDataSource);
				if ($intCount == 0)
					$strToReturn .= sprintf($this->strLabelForNoneFound, $this->strNounPlural);
				else if ($intCount == 1)
					$strToReturn .= sprintf($this->strLabelForOneFound, $this->strNoun);
				else
					$strToReturn .= sprintf($this->strLabelForMultipleFound, $intCount, $this->strNounPlural);
			}

			$strToReturn .= '</td><td valign="bottom" style="width:50%;font-size:10px;text-align:right;">';
			$strToReturn .= $objPaginator->Render(false);
			$strToReturn .= '</td></tr></table></td></tr>';
			
			return $strToReturn;
		}

		protected function GetHeaderRowHtml() {
			$objHeaderStyle = $this->objRowStyle->ApplyOverride($this->objHeaderRowStyle);

			$strToReturn = sprintf('<tr %s>', $objHeaderStyle->GetAttributes());
			$intColumnIndex = 0;
			if ($this->objColumnArray) foreach ($this->objColumnArray as $objColumn) {
				if ($objColumn->OrderByClause) {						
					// This Column is Sortable
					$strArrowImage = "";
					$strName = $objColumn->Name;

					if ($intColumnIndex == $this->intSortColumnIndex) {
						$strName = strtoupper($strName);
						if ($this->intSortDirection == 0)
							$strArrowImage = sprintf(' <img src="%s/sort_arrow.png" width="7" height="7" alt="Sorted" />', __VIRTUAL_DIRECTORY__ . __IMAGE_ASSETS__);
						else
							$strArrowImage = sprintf(' <img src="%s/sort_arrow_reverse.png" width="7" height="7" alt="Reverse Sorted" />', __VIRTUAL_DIRECTORY__ . __IMAGE_ASSETS__);
					}

					$this->strActionParameter = $intColumnIndex;

					$strToReturn .= sprintf('<th %s><a href="#" %s%s>%s</a>%s</th>',
						$this->objHeaderRowStyle->GetAttributes(),
						$this->GetActionAttributes(),
						$this->objHeaderLinkStyle->GetAttributes(),
						$strName,
						$strArrowImage);
				} else
					$strToReturn .= sprintf('<th %s>%s</th>', $this->objHeaderRowStyle->GetAttributes(), $objColumn->Name);
				$intColumnIndex++;
			}
			$strToReturn .= '</tr>';

			return $strToReturn;
		}
		
		protected function GetDataGridRowHtml($objObject) {
			// Get the Default Style
			$objStyle = $this->objRowStyle;

			// Iterate through the Columns
			$strColumnsHtml = '';
			foreach ($this->objColumnArray as $objColumn) {
				try {
					$strHtml = $this->ParseColumnHtml($objColumn, $objObject);

					if ($objColumn->HtmlEntities)
						$strHtml = QApplication::HtmlEntities($strHtml);

					 // For IE
					if (QApplication::IsBrowser(QBrowserType::InternetExplorer) &&
						($strHtml == ''))
							$strHtml = '&nbsp;';
				} catch (QCallerException $objExc) {
					$objExc->IncrementOffset();
					throw $objExc;
				}
				$strColumnsHtml .= sprintf('<td %s>%s</td>', $objColumn->GetAttributes(), $strHtml);
			}

			// Apply AlternateRowStyle (if applicable)
			if (($this->intCurrentRowIndex % 2) == 1)
				$objStyle = $objStyle->ApplyOverride($this->objAlternateRowStyle);

			// Apply any Style Override (if applicable)
			if ((is_array($this->objOverrideRowStyleArray)) && 
				(array_key_exists($this->intCurrentRowIndex, $this->objOverrideRowStyleArray)) &&
				(!is_null($this->objOverrideRowStyleArray[$this->intCurrentRowIndex])))
				$objStyle = $objStyle->ApplyOverride($this->objOverrideRowStyleArray[$this->intCurrentRowIndex]);

			// Finish up
			$strToReturn = sprintf('<tr %s>%s</tr>', $objStyle->GetAttributes(), $strColumnsHtml);
			$this->intCurrentRowIndex++;
			return $strToReturn;
		}

		protected function GetFooterRowHtml() {}

		protected function GetControlHtml() {
			$this->DataBind();

			// Table Tag
			$strStyle = $this->GetStyleAttributes();
			if ($strStyle)
				$strStyle = sprintf('style="%s" ', $strStyle);
			$strToReturn = sprintf('<table %s%s>', $this->GetAttributes(), $strStyle);

			// Paginator Row (if applicable)
			if ($this->objPaginator)
				$strToReturn .= $this->GetPaginatorRowHtml($this->objPaginator);

			// Header Row (if applicable)
			if ($this->blnShowHeader)
				$strToReturn .= $this->GetHeaderRowHtml();

			// DataGrid Rows
			$this->intCurrentRowIndex = 0;
			if ($this->objDataSource)
				foreach ($this->objDataSource as $objObject)
					$strToReturn .= $this->GetDataGridRowHtml($objObject);

			// Footer Row (if applicable)
			if ($this->blnShowFooter)
				$strToReturn .= $this->GetFooterRowHtml();

			// Finish Up
			$strToReturn .= '</table>';
			$this->objDataSource = null;
			
			return $strToReturn;
		}


		/////////////////////////
		// Public Properties: GET
		/////////////////////////
		public function __get($strName) {
			switch ($strName) {
				// APPEARANCE
				case "AlternateRowStyle": return $this->objAlternateRowStyle;
				case "BorderCollapse": return $this->strBorderCollapse;
				case "HeaderRowStyle": return $this->objHeaderRowStyle;
				case "HeaderLinkStyle": return $this->objHeaderLinkStyle;
				case "RowStyle": return $this->objRowStyle;

				// LAYOUT
				case "CellPadding": return $this->intCellPadding;
				case "CellSpacing": return $this->intCellSpacing;
				case "GridLines": return $this->strGridLines;
				case "ShowHeader": return $this->blnShowHeader;
				case "ShowFooter": return $this->blnShowFooter;

				// MISC
				case "OrderByClause":
					if ($this->intSortColumnIndex >= 0) {
						if ($this->intSortDirection == 0)
							return $this->objColumnArray[$this->intSortColumnIndex]->OrderByClause;
						else
							return $this->objColumnArray[$this->intSortColumnIndex]->ReverseOrderByClause;
					} else
						return null;
				case "SortInfo":
					if ($this->intSortColumnIndex >= 0) {
						if ($this->intSortDirection == 0) {
							$mixToReturn = $this->objColumnArray[$this->intSortColumnIndex]->SortByCommand;
							if ($mixToReturn instanceof QQOrderBy)
								return $mixToReturn->GetAsManualSql();
							else
								return $mixToReturn;
						} else {
							$mixToReturn = $this->objColumnArray[$this->intSortColumnIndex]->ReverseSortByCommand;
							if ($mixToReturn instanceof QQOrderBy)
								return $mixToReturn->GetAsManualSql();
							else
								return $mixToReturn;
						}
					} else
						return null;

				case "CurrentRowIndex": return $this->intCurrentRowIndex;
				case "SortColumnIndex": return $this->intSortColumnIndex;
				case "SortDirection": return $this->intSortDirection;

				case 'LabelForNoneFound': return $this->strLabelForNoneFound;
				case 'LabelForOneFound': return $this->strLabelForOneFound;
				case 'LabelForMultipleFound': return $this->strLabelForMultipleFound;
				case 'LabelForPaginated': return $this->strLabelForPaginated;

				default:
					try {
						return parent::__get($strName);
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}


		/////////////////////////
		// Public Properties: SET
		/////////////////////////
		public function __set($strName, $mixValue) {
			switch ($strName) {
				// APPEARANCE
				case "AlternateRowStyle":
					try {
						$this->objAlternateRowStyle = QType::Cast($mixValue, "QDataGridRowStyle");
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
				case "BorderCollapse":
					try {
						$this->strBorderCollapse = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
				case "HeaderRowStyle":
					try {
						$this->objHeaderRowStyle = QType::Cast($mixValue, "QDataGridRowStyle");
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
				case "HeaderLinkStyle":
					try {
						$this->objHeaderLinkStyle = QType::Cast($mixValue, "QDataGridRowStyle");
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
				case "RowStyle":
					try {
						$this->objRowStyle = QType::Cast($mixValue, "QDataGridRowStyle");
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
					
				// BEHAVIOR
				case "UseAjax":
					try {
						$blnToReturn = parent::__set($strName, $mixValue);
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

					// Because we are switching to/from Ajax, we need to reset the events
					$this->RemoveAllActions('onclick');
					if ($this->blnUseAjax)
						$this->AddAction(new QClickEvent(), new QAjaxControlAction($this, 'Sort_Click'));
					else
						$this->AddAction(new QClickEvent(), new QServerControlAction($this, 'Sort_Click'));

					$this->AddAction(new QClickEvent(), new QTerminateAction());
					return $blnToReturn;

				// LAYOUT
				case "CellPadding":
					try {
						$this->intCellPadding = QType::Cast($mixValue, QType::Integer);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
				case "CellSpacing":
					try {
						$this->intCellSpacing = QType::Cast($mixValue, QType::Integer);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
				case "GridLines":
					try {
						$this->strGridLines = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
				case "ShowHeader":
					try {
						$this->blnShowHeader = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "ShowFooter":
					try {
						$this->blnShowFooter = QType::Cast($mixValue, QType::Boolean);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				// MISC
				case "SortColumnIndex":
					try {
						$this->intSortColumnIndex = QType::Cast($mixValue, QType::Integer);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case "SortDirection":
					if ($mixValue == 1)
						$this->intSortDirection = 1;
					else
						$this->intSortDirection = 0;
					break;


				case 'LabelForNoneFound':
					try {
						$this->strLabelForNoneFound = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case 'LabelForOneFound':
					try {
						$this->strLabelForOneFound = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case 'LabelForMultipleFound':
					try {
						$this->strLabelForMultipleFound = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				case 'LabelForPaginated':
					try {
						$this->strLabelForPaginated = QType::Cast($mixValue, QType::String);
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}

				default:
					try {
						parent::__set($strName, $mixValue);
						break;
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}
	}
?>