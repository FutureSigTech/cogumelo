<?php

class TableView {


  static function getTableHtml( $tableId, $tableDataUrl ) {

  $tableHtml = '


  <script>
    $(function() {
      if( typeof pageTables == "undefined"){
        var cogumeloTables = {};
      }

      cogumeloTables.'. $tableId .' = new cogumeloTable("'. $tableId . '", "' . $tableDataUrl .'");
    });
  </script>

  <!-- HTML TABLE id: "' . $tableId . '" data url: "' . $tableDataUrl . '" -->
  <div class="' . $tableId . ' tableContainer">
    <div class="tableHeaderContainer">
      <div class="tableSearchFilters clearfix">

        <div class="tableFilters clearfix">
          <select>
            <option value="1">Aceptado</option>
            <option value="2">Recibido</option>
            <option value="3">Rechazado</option>
          </select>
          <button class="openFilters">+Filtros</button>
        </div>
        <div class="tableSearch clearfix">
          <input type="text"><button>Search</button>
        </div>
      </div>
      <div class="tableMoreFilters clearfix" style="display:none;">
        <div class="MoreFilters" >
          <div class="clearfix">
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>

              <div class="FilterMain">
                <label>Categoria</label>
                <select>
                  <option value="1">Categoria 1</option>
                  <option value="2">Categoria 2</option>
                  <option value="3">Categoria 3</option>
                </select>
                <div class="FilterMain">
                  <label>Categoria</label>
                  <select>
                    <option value="1">Categoria 1</option>
                    <option value="2">Categoria 2</option>
                    <option value="3">Categoria 3</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
            <div class="FilterMain">
              <label>Categoria</label>
              <select>
                <option value="1">Categoria 1</option>
                <option value="2">Categoria 2</option>
                <option value="3">Categoria 3</option>
              </select>
            </div>
          </div>

          <div class="buttonsContainer">
            <button class="clearFilters">Eliminar filtros</button>
            <button class="closeFilters">Cerrar</button>
          </div>
        </div>
      </div>
      <div class="tableResumeFilters clearfix">
        <span>Estas filtrando por: (Categoria 1, Aceptado).</span>
        <button class="clearFilters">Eliminar filtros</button>
      </div>
      <div class="tableActions clearfix">
        <div class="selectAll"><input type="checkbox"></div>
        <div class="addElem"><img src="media/module/table/img/add.png" alt="Add"></div>
        <div class="exportContainer"><img src="media/module/table/img/export.png" alt="Export"></div>
        <select>
          <option value="1">Action 1</option>
          <option value="2">Action 2</option>
          <option value="3">Action 3</option>
        </select>
        <!-- Paginador -->
        <div class="tablePaginator">
          <div class="tablePage"><input type="text" value="100"> de 219</div>
          <div class="tablePreviousPage"><img src="media/module/table/img/a-left.png" alt="previous page"></div>
          <div class="tableNextPage"><img src="media/module/table/img/a-right.png" alt="next page"></div>
        </div>
      </div>

    </div>
    <table class="tableClass clearfix">

    <tr>
      <th></th>
      <th>
        <div class="clearfix">
          <div>Name</div>
          <div><img src="media/module/table/img/up.png"></div>
        </div>
      </th>
      <th>
        <div class="clearfix">
          <div>Description</div>
          <div><img src="media/module/table/img/up.png"></div>
        </div>
      </th>
      <th>
        <div class="clearfix">
          <div>Date</div>
          <div><img src="media/module/table/img/up.png"></div>
        </div>
      </th>
      <th>
        <div class="clearfix">
          <div>Province</div>
          <div><img src="media/module/table/img/up.png"></div>
        </div>
      </th>
      <th>
        <div class="clearfix">
          <div>Status</div>
          <div><img src="media/module/table/img/down.png"></div>
        </div>
      </th>
    </tr>

    </table>
    <!-- Paginador -->
    <div class="tablePaginator ">
      <div class="tablePage"><input type="text" value="100"> de 219</div>
      <div class="tablePreviousPage"><img src="media/module/table/img/a-left.png" alt="previous page"></div>
      <div class="tableNextPage"><img src="media/module/table/img/a-right.png" alt="next page"></div>
    </div>
  </div>
  <!-- END HTML TABLE id: "' . $tableId . '" data url: "' . $tableDataUrl . '" -->

    ';

    return $tableHtml;
  }


}