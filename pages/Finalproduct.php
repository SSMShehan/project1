<?php
include '../addphp/navbar.php';
/*require_once 'config/db_config.php';*/
?>

<table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Item No</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>0001</td>
                            <td>T-Shirt</td>
                            <td>Cotton, Size M</td>
                            <td>$20</td>
                            <td><span class="status in-stock">In Stock</span></td>
                            <td>2024-10-01</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Jeans</td>
                            <td>Denim, Size 32</td>
                            <td>$45</td>
                            <td><span class="status out-of-stock">Out of Stock</span></td>
                            <td>2024-09-25</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Jacket</td>
                            <td>Leather, Size L</td>
                            <td>$120</td>
                            <td><span class="status in-stock">In Stock</span></td>
                            <td>2024-10-05</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Dress</td>
                            <td>Silk, Size S</td>
                            <td>$60</td>
                            <td><span class="status in-stock">In Stock</span></td>
                            <td>2024-09-30</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td>5</td>
                            <td>Dress</td>
                            <td>Silk, Size S</td>
                            <td>$60</td>
                            <td><span class="status in-stock">In Stock</span></td>
                            <td>2024-09-30</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td>6</td>
                            <td>Dress</td>
                            <td>Silk, Size S</td>
                            <td>$60</td>
                            <td><span class="status in-stock">In Stock</span></td>
                            <td>2024-09-30</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td>7</td>
                            <td>Dress</td>
                            <td>Silk, Size S</td>
                            <td>$60</td>
                            <td><span class="status in-stock">In Stock</span></td>
                            <td>2024-09-30</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td>8</td>
                            <td>Dress</td>
                            <td>Silk, Size S</td>
                            <td>$60</td>
                            <td><span class="status in-stock">In Stock</span></td>
                            <td>2024-09-30</td>
                            <td>
                                <button class="btn-edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>

                    </tbody>
                </table>

<?php
include '../addphp/footer.php';
/*require_once 'config/db_config.php';*/
?>