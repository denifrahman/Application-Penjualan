<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ------------------------------------------------------------------------
 * CLASS NAME : Barang
 * ------------------------------------------------------------------------
 *
 * @author     Muhammad Akbar <muslim.politekniktelkom@gmail.com>
 * @copyright  2016
 * @license    http://aplikasiphp.net
 *
 */

class Barang extends MY_Controller
{
	public function index()
	{
		$this->load->view('barang/barang_data');
	}

	public function barang_json()
	{
		$this->load->model('m_barang');
		$level 			= $this->session->userdata('ap_level');

		$requestData	= $_REQUEST;
		$fetch			= $this->m_barang->fetch_data_barang($requestData['search']['value'], $requestData['order'][0]['column'], $requestData['order'][0]['dir'], $requestData['start'], $requestData['length']);

		$totalData		= $fetch['totalData'];
		$totalFiltered	= $fetch['totalFiltered'];
		$query			= $fetch['query'];

		$data	= array();
		foreach ($query->result_array() as $row) {
			$nestedData = array();

			$nestedData[]	= $row['nomor'];
			$nestedData[]	= $row['kode_barang'];
			$nestedData[]	= $row['nama_barang'];
			$nestedData[]	= $row['kategori'];
			$nestedData[]	= $row['merk'];
			$nestedData[]	= ($row['total_stok'] == 'Kosong') ? "<font color='red'><b>" . $row['total_stok'] . "</b></font>" : $row['total_stok'];
			$nestedData[]	= $row['harga'];
			$nestedData[]	= preg_replace("/\r\n|\r|\n/", '<br />', $row['keterangan']);

			if ($level == 'admin' or $level == 'inventory') {
				$nestedData[]	= "<a href='" . site_url('barang/edit/' . $row['id_barang']) . "' id='EditBarang'><i class='fa fa-pencil'></i> Edit</a>";
				$nestedData[]	= "<a href='" . site_url('barang/hapus/' . $row['id_barang']) . "' id='HapusBarang'><i class='fa fa-trash-o'></i> Hapus</a>";
			}

			$data[] = $nestedData;
		}

		$json_data = array(
			"draw"            => intval($requestData['draw']),
			"recordsTotal"    => intval($totalData),
			"recordsFiltered" => intval($totalFiltered),
			"data"            => $data
		);

		echo json_encode($json_data);
	}

	public function hapus($id_barang)
	{
		$level = $this->session->userdata('ap_level');
		if ($level == 'admin' or $level == 'inventory') {
			if ($this->input->is_ajax_request()) {
				$this->load->model('m_barang');
				$hapus = $this->m_barang->hapus_barang($id_barang);
				if ($hapus) {
					echo json_encode(array(
						"pesan" => "<font color='green'><i class='fa fa-check'></i> Data berhasil dihapus !</font>
					"
					));
				} else {
					echo json_encode(array(
						"pesan" => "<font color='red'><i class='fa fa-warning'></i> Terjadi kesalahan, coba lagi !</font>
					"
					));
				}
			}
		}
	}

	public function tambah()
	{
		$level = $this->session->userdata('ap_level');
		if ($level == 'admin' or $level == 'inventory') {
			if ($_POST) {
				$this->load->library('form_validation');
				// config upload path
				$config['upload_path']          = './assets/img/barang/';
				$config['allowed_types']        = 'gif|jpg|png';
				$config['max_size']             = 9000;

				$this->load->library('upload', $config);
				// upload multiple foto
				$files = $_FILES;
				$count_foto = count($_FILES['foto']['name']);

				$no = 0;
				foreach ($_POST['kode'] as $kode) {
					$this->form_validation->set_rules('kode[' . $no . ']', 'Kode Barang #' . ($no + 1), 'trim|required|alpha_numeric|max_length[40]|callback_exist_kode[kode[' . $no . ']]');
					$this->form_validation->set_rules('nama[' . $no . ']', 'Nama Barang #' . ($no + 1), 'trim|required|max_length[60]|alpha_numeric_spaces');
					$this->form_validation->set_rules('id_kategori_barang[' . $no . ']', 'Kategori #' . ($no + 1), 'trim|required');
					$this->form_validation->set_rules('id_merk_barang[' . $no . ']', 'Merek #' . ($no + 1), 'trim');
					$this->form_validation->set_rules('stok[' . $no . ']', 'Stok #' . ($no + 1), 'trim|required|numeric|max_length[10]|callback_cek_titik[stok[' . $no . ']]');
					$this->form_validation->set_rules('harga[' . $no . ']', 'Harga #' . ($no + 1), 'trim|required|numeric|min_length[4]|max_length[10]|callback_cek_titik[harga[' . $no . ']]');
					$this->form_validation->set_rules('keterangan[' . $no . ']', 'Keterangan #' . ($no + 1), 'trim|max_length[2000]');
					$no++;
				}

				$this->form_validation->set_message('required', '%s harus diisi !');
				$this->form_validation->set_message('numeric', '%s harus angka !');
				$this->form_validation->set_message('exist_kode', '%s sudah ada di database, pilih kode lain yang unik !');
				$this->form_validation->set_message('cek_titik', '%s harus angka, tidak boleh ada titik !');
				$this->form_validation->set_message('alpha_numeric_spaces', '%s Harus huruf / angka !');
				$this->form_validation->set_message('alpha_numeric', '%s Harus huruf / angka !');
				if ($this->form_validation->run() == TRUE) {
					$this->load->model('m_barang');

					$no_array = 0;
					$inserted = 0;
					foreach ($_POST['kode'] as $k) {
						$kode 				= $_POST['kode'][$no_array];
						$nama 				= $_POST['nama'][$no_array];
						$id_kategori_barang	= $_POST['id_kategori_barang'][$no_array];
						$id_merk_barang		= $_POST['id_merk_barang'][$no_array];
						$stok 				= $_POST['stok'][$no_array];
						$harga 				= $_POST['harga'][$no_array];
						$keterangan 		= $this->clean_tag_input($_POST['keterangan'][$no_array]);
						$sampul = '';
						$foto1 = '';
						$foto2 = '';
						$foto3 = '';

						for ($i = 0; $i < $count_foto; $i++) {
							// $dname = explode(".", $files['foto']['name'][$i]);
							$ext = pathinfo($files['foto']['name'][$i], PATHINFO_EXTENSION);
							$_FILES['foto']['name'] = time() . $i . '.' . $ext;
							$_FILES['foto']['type'] = $files['foto']['type'][$i];
							$_FILES['foto']['tmp_name'] = $files['foto']['tmp_name'][$i];
							$_FILES['foto']['error'] = $files['foto']['error'][$i];
							$_FILES['foto']['size'] = $files['foto']['size'][$i];

							// $this->upload->initialize($this->set_upload_options($file_path));
							if (!($this->upload->do_upload('foto')) || $files['foto']['error'][$i] != 0) {
								print_r($this->upload->display_errors());
							} else {
								$sampul = base_url() . 'assets/img/barang/' . time() . '0' . '.' . $ext;
								$foto1 =  base_url() . 'assets/img/barang/' . time() . '1' . '.' . $ext;
								$foto2 =  base_url() . 'assets/img/barang/' . time() . '2' . '.' . $ext;
								$foto3 =  base_url() . 'assets/img/barang/' . time() . '3' . '.' . $ext;
							}
						}
						$insert = $this->m_barang->tambah_baru($kode, $nama, $id_kategori_barang, $id_merk_barang, $stok, $harga, $keterangan, $sampul, $foto1, $foto2, $foto3);
						if ($insert) {
							$inserted++;
						}
						$no_array++;
					}

					if ($inserted > 0) {
						echo json_encode(array(
							'status' => 1,
							'pesan' => "<i class='fa fa-check' style='color:green;'></i> Data barang berhasil dismpan."
						));
					} else {
						$this->query_error("Oops, terjadi kesalahan, coba lagi !");
					}
				} else {
					$this->input_error();
				}
			} else {
				$this->load->model('m_kategori_barang');
				$this->load->model('m_merk_barang');

				$dt['kategori'] = $this->m_kategori_barang->get_all();
				$dt['merek'] 	= $this->m_merk_barang->get_all();
				$this->load->view('barang/barang_tambah', $dt);
			}
		} else {
			exit();
		}
	}

	public function ajax_cek_kode()
	{
		if ($this->input->is_ajax_request()) {
			$kode = $this->input->post('kodenya');
			$this->load->model('m_barang');

			$cek_kode = $this->m_barang->cek_kode($kode);
			if ($cek_kode->num_rows() > 0) {
				echo json_encode(array(
					'status' => 0,
					'pesan' => "<font color='red'>Kode sudah ada</font>"
				));
			} else {
				echo json_encode(array(
					'status' => 1,
					'pesan' => ''
				));
			}
		}
	}

	public function exist_kode($kode)
	{
		$this->load->model('m_barang');
		$cek_kode = $this->m_barang->cek_kode($kode);

		if ($cek_kode->num_rows() > 0) {
			return FALSE;
		}
		return TRUE;
	}

	public function cek_titik($angka)
	{
		$pecah = explode('.', $angka);
		if (count($pecah) > 1) {
			return FALSE;
		}
		return TRUE;
	}

	public function edit($id_barang = NULL)
	{
		// var_dump($_POST)
		if (!empty($id_barang)) {
			$level = $this->session->userdata('ap_level');
			if ($level == 'admin' or $level == 'inventory') {
				if ($this->input->is_ajax_request()) {
					$this->load->model('m_barang');

					if ($_POST) {
						// config upload path
						$config['upload_path']          = './assets/img/barang/';
						$config['allowed_types']        = 'gif|jpg|png';
						$config['max_size']             = 9000;

						$this->load->library('upload', $config);
						// upload multiple foto

						// $count_foto = count($_FILES['foto']['name']);

						$this->load->library('form_validation');

						$kode_barang 		= $this->input->post('kode_barang');
						$kode_barang_old	= $this->input->post('kode_barang_old');

						$callback			= '';
						if ($kode_barang !== $kode_barang_old) {
							$callback = "|callback_exist_kode[kode_barang]";
						}

						$this->form_validation->set_rules('kode_barang', 'Kode Barang', 'trim|required|alpha_numeric|max_length[40]' . $callback);
						$this->form_validation->set_rules('nama_barang', 'Nama Barang', 'trim|required|max_length[60]|alpha_numeric_spaces');
						$this->form_validation->set_rules('id_kategori_barang', 'Kategori', 'trim|required');
						$this->form_validation->set_rules('id_merk_barang', 'Merek', 'trim');
						$this->form_validation->set_rules('total_stok', 'Stok', 'trim|required|numeric|max_length[10]|callback_cek_titik[total_stok]');
						$this->form_validation->set_rules('harga', 'Harga', 'trim|required|numeric|min_length[4]|max_length[10]|callback_cek_titik[harga]');
						$this->form_validation->set_rules('keterangan', 'Keterangan', 'trim|max_length[2000]');
						// $this->form_validation->set_rules('foto[]', 'Foto', 'trim|required');

						$this->form_validation->set_message('required', '%s harus diisi !');
						$this->form_validation->set_message('numeric', '%s harus angka !');
						$this->form_validation->set_message('exist_kode', '%s sudah ada di database, pilih kode lain yang unik !');
						$this->form_validation->set_message('cek_titik', '%s harus angka, tidak boleh ada titik !');
						$this->form_validation->set_message('alpha_numeric_spaces', '%s Harus huruf / angka !');
						$this->form_validation->set_message('alpha_numeric', '%s Harus huruf / angka !');

						if ($this->form_validation->run() == TRUE) {
							$nama 				= $this->input->post('nama_barang');
							$id_kategori_barang	= $this->input->post('id_kategori_barang');
							$id_merk_barang		= $this->input->post('id_merk_barang');
							$stok 				= $this->input->post('total_stok');
							$harga 				= $this->input->post('harga');
							$keterangan 		= $this->clean_tag_input($this->input->post('keterangan'));
							$files = $_FILES;
							$sampul = $this->input->post('foto_sampul');
							$foto1 = $this->input->post('foto_1');
							$foto2 = $this->input->post('foto_2');
							$foto3 = $this->input->post('foto_3');
							// var_dump($sampul);
							$err = false;
							$message = 'lengkapi foto';
							// if ($sampul == '' || $foto1 == '' || $foto2 == '' || $foto3 == '') {

							// } else {

							// // if ($files) {
							// if (!$files['foto_sampul']['name']) {
							// 	$err = true;
							// 	$message = 'Silahkan pilih foto sampul';
							// } else if (!$files['foto_1']['name']) {
							// 	$err = true;
							// 	$message = 'Silahkan pilih foto 1';
							// } else if (!$files['foto_2']['name']) {
							// 	$err = true;
							// 	$message = 'Silahkan pilih foto 2';
							// } else if (!$files['foto_3']['name']) {
							// 	$err = true;
							// 	$message = 'Silahkan pilih foto 3';
							// }

							// foto sampul
							if ($files['foto_sampul']['name']) {
								$ext = pathinfo($files['foto_sampul']['name'], PATHINFO_EXTENSION);
								$name = 'foto_sampul_' . time() . '.' . $ext;
								$_FILES['foto_sampul']['name'] = $name;
								$_FILES['foto_sampul']['type'] = $files['foto_sampul']['type'];
								$_FILES['foto_sampul']['tmp_name'] = $files['foto_sampul']['tmp_name'];
								$_FILES['foto_sampul']['error'] = $files['foto_sampul']['error'];
								$_FILES['foto_sampul']['size'] = $files['foto_sampul']['size'];
								$sampul = base_url() . 'assets/img/barang/' . $name;
								if (!($this->upload->do_upload('foto_sampul'))) {
								}
							}
							// // foto 1
							if ($files['foto_1']['name']) {
								$ext = pathinfo($files['foto_1']['name'], PATHINFO_EXTENSION);
								$name = 'foto_1_' . time() . '.' . $ext;
								$_FILES['foto_1']['name'] = $name;
								$_FILES['foto_1']['type'] = $files['foto_1']['type'];
								$_FILES['foto_1']['tmp_name'] = $files['foto_1']['tmp_name'];
								$_FILES['foto_1']['error'] = $files['foto_1']['error'];
								$_FILES['foto_1']['size'] = $files['foto_1']['size'];
								$foto1 = base_url() . 'assets/img/barang/' . $name;
								if (!($this->upload->do_upload('foto_1'))) {
								}
							}
							// foto 2
							if ($files['foto_2']['name']) {
								$ext = pathinfo($files['foto_2']['name'], PATHINFO_EXTENSION);
								$name = 'foto_2_' . time() . '.' . $ext;
								$_FILES['foto_2']['type'] = $name;
								$_FILES['foto_2']['tmp_name'] = $files['foto_2']['tmp_name'];
								$_FILES['foto_2']['error'] = $files['foto_2']['error'];
								$_FILES['foto_2']['size'] = $files['foto_2']['size'];
								$foto2 = base_url() . 'assets/img/barang/' . $name;
								if (!($this->upload->do_upload('foto_2'))) {
								}
							}
							// // foto 3
							if ($files['foto_3']['name']) {
								$ext = pathinfo($files['foto_3']['name'], PATHINFO_EXTENSION);
								$name = 'foto_3_' . time() . '.' . $ext;
								$_FILES['foto_3']['name'] = $name;
								$_FILES['foto_3']['type'] = $files['foto_3']['type'];
								$_FILES['foto_3']['tmp_name'] = $files['foto_3']['tmp_name'];
								$_FILES['foto_3']['error'] = $files['foto_3']['error'];
								$_FILES['foto_3']['size'] = $files['foto_3']['size'];
								$foto3 = base_url() . 'assets/img/barang/' . $name;
								if (!($this->upload->do_upload('foto_3'))) {
								}
							}
							// } else {

							// }

							// for ($i = 0; $i < $count_foto; $i++) {
							// 	$ext = pathinfo($files['foto']['name'][$i], PATHINFO_EXTENSION);
							// 	$_FILES['foto']['name'] = time() . $i . '.' . $ext;
							// 	$_FILES['foto']['type'] = $files['foto']['type'][$i];
							// 	$_FILES['foto']['tmp_name'] = $files['foto']['tmp_name'][$i];
							// 	$_FILES['foto']['error'] = $files['foto']['error'][$i];
							// 	$_FILES['foto']['size'] = $files['foto']['size'][$i];
							// 	if (!($this->upload->do_upload('foto')) || $files['foto']['error'][$i] != 0) {
							// 		$err = true;
							// 	} else {
							// 		$sampul = base_url() . 'assets/img/barang/' . time() . '0' . '.' . $ext;
							// 		$foto1 =  base_url() . 'assets/img/barang/' . time() . '1' . '.' . $ext;
							// 		$foto2 =  base_url() . 'assets/img/barang/' . time() . '2' . '.' . $ext;
							// 		$foto3 =  base_url() . 'assets/img/barang/' . time() . '3' . '.' . $ext;
							// 	}
							// }


							$update = $this->m_barang->update_barang($id_barang, $kode_barang, $nama,  $id_kategori_barang, $id_merk_barang, $stok, $harga, $keterangan, $sampul, $foto1, $foto2, $foto3);
							if ($update) {
								echo json_encode(array(
									'status' => 1,
									'pesan' => "<div class='alert alert-success'><i class='fa fa-check'></i> Data barang berhasil diupdate.</div>"
								));
							} else {
								$this->query_error();
							}



							// }
						} else {
							$this->input_error();
						}
					} else {
						$this->load->model('m_kategori_barang');
						$this->load->model('m_merk_barang');

						$dt['barang'] 	= $this->m_barang->get_baris($id_barang)->row();
						$dt['kategori'] = $this->m_kategori_barang->get_all();
						$dt['merek'] 	= $this->m_merk_barang->get_all();
						$this->load->view('barang/barang_edit', $dt);
					}
				}
			}
		}
	}

	public function list_merek()
	{
		$this->load->view('barang/merek/merek_data');
	}

	public function list_merek_json()
	{
		$this->load->model('m_merk_barang');
		$level 			= $this->session->userdata('ap_level');

		$requestData	= $_REQUEST;
		$fetch			= $this->m_merk_barang->fetch_data_merek($requestData['search']['value'], $requestData['order'][0]['column'], $requestData['order'][0]['dir'], $requestData['start'], $requestData['length']);

		$totalData		= $fetch['totalData'];
		$totalFiltered	= $fetch['totalFiltered'];
		$query			= $fetch['query'];

		$data	= array();
		foreach ($query->result_array() as $row) {
			$nestedData = array();

			$nestedData[]	= $row['nomor'];
			$nestedData[]	= $row['merk'];

			if ($level == 'admin' or $level == 'inventory') {
				$nestedData[]	= "<a href='" . site_url('barang/edit-merek/' . $row['id_merk_barang']) . "' id='EditMerek'><i class='fa fa-pencil'></i> Edit</a>";
				$nestedData[]	= "<a href='" . site_url('barang/hapus-merek/' . $row['id_merk_barang']) . "' id='HapusMerek'><i class='fa fa-trash-o'></i> Hapus</a>";
			}

			$data[] = $nestedData;
		}

		$json_data = array(
			"draw"            => intval($requestData['draw']),
			"recordsTotal"    => intval($totalData),
			"recordsFiltered" => intval($totalFiltered),
			"data"            => $data
		);

		echo json_encode($json_data);
	}

	public function tambah_merek()
	{
		$level = $this->session->userdata('ap_level');
		if ($level == 'admin' or $level == 'inventory') {
			if ($_POST) {
				$this->load->library('form_validation');
				$this->form_validation->set_rules('merek', 'Merek', 'trim|required|max_length[40]|alpha_numeric_spaces');
				$this->form_validation->set_message('required', '%s harus diisi !');
				$this->form_validation->set_message('alpha_numeric_spaces', '%s Harus huruf / angka !');

				if ($this->form_validation->run() == TRUE) {
					$this->load->model('m_merk_barang');
					$merek 	= $this->input->post('merek');
					$insert = $this->m_merk_barang->tambah_merek($merek);
					if ($insert) {
						echo json_encode(array(
							'status' => 1,
							'pesan' => "<div class='alert alert-success'><i class='fa fa-check'></i> <b>" . $merek . "</b> berhasil ditambahkan.</div>"
						));
					} else {
						$this->query_error();
					}
				} else {
					$this->input_error();
				}
			} else {
				$this->load->view('barang/merek/merek_tambah');
			}
		}
	}

	public function hapus_merek($id_merk_barang)
	{
		$level = $this->session->userdata('ap_level');
		if ($level == 'admin' or $level == 'inventory') {
			if ($this->input->is_ajax_request()) {
				$this->load->model('m_merk_barang');
				$hapus = $this->m_merk_barang->hapus_merek($id_merk_barang);
				if ($hapus) {
					echo json_encode(array(
						"pesan" => "<font color='green'><i class='fa fa-check'></i> Data berhasil dihapus !</font>
					"
					));
				} else {
					echo json_encode(array(
						"pesan" => "<font color='red'><i class='fa fa-warning'></i> Terjadi kesalahan, coba lagi !</font>
					"
					));
				}
			}
		}
	}

	public function edit_merek($id_merk_barang = NULL)
	{
		if (!empty($id_merk_barang)) {
			$level = $this->session->userdata('ap_level');
			if ($level == 'admin' or $level == 'inventory') {
				if ($this->input->is_ajax_request()) {
					$this->load->model('m_merk_barang');

					if ($_POST) {
						$this->load->library('form_validation');
						$this->form_validation->set_rules('merek', 'Merek', 'trim|required|max_length[40]|alpha_numeric_spaces');
						$this->form_validation->set_message('required', '%s harus diisi !');
						$this->form_validation->set_message('alpha_numeric_spaces', '%s Harus huruf / angka !');

						if ($this->form_validation->run() == TRUE) {
							$merek 	= $this->input->post('merek');
							$insert = $this->m_merk_barang->update_merek($id_merk_barang, $merek);
							if ($insert) {
								echo json_encode(array(
									'status' => 1,
									'pesan' => "<div class='alert alert-success'><i class='fa fa-check'></i> Data berhasil diupdate.</div>"
								));
							} else {
								$this->query_error();
							}
						} else {
							$this->input_error();
						}
					} else {
						$dt['merek'] = $this->m_merk_barang->get_baris($id_merk_barang)->row();
						$this->load->view('barang/merek/merek_edit', $dt);
					}
				}
			}
		}
	}

	public function list_kategori()
	{
		$this->load->view('barang/kategori/kategori_data');
	}

	public function list_kategori_json()
	{
		$this->load->model('m_kategori_barang');
		$level 			= $this->session->userdata('ap_level');

		$requestData	= $_REQUEST;
		$fetch			= $this->m_kategori_barang->fetch_data_kategori($requestData['search']['value'], $requestData['order'][0]['column'], $requestData['order'][0]['dir'], $requestData['start'], $requestData['length']);

		$totalData		= $fetch['totalData'];
		$totalFiltered	= $fetch['totalFiltered'];
		$query			= $fetch['query'];

		$data	= array();
		foreach ($query->result_array() as $row) {
			$nestedData = array();

			$nestedData[]	= $row['nomor'];
			$nestedData[]	= $row['kategori'];

			if ($level == 'admin' or $level == 'inventory') {
				$nestedData[]	= "<a href='" . site_url('barang/edit-kategori/' . $row['id_kategori_barang']) . "' id='EditKategori'><i class='fa fa-pencil'></i> Edit</a>";
				$nestedData[]	= "<a href='" . site_url('barang/hapus-kategori/' . $row['id_kategori_barang']) . "' id='HapusKategori'><i class='fa fa-trash-o'></i> Hapus</a>";
			}

			$data[] = $nestedData;
		}

		$json_data = array(
			"draw"            => intval($requestData['draw']),
			"recordsTotal"    => intval($totalData),
			"recordsFiltered" => intval($totalFiltered),
			"data"            => $data
		);

		echo json_encode($json_data);
	}

	public function tambah_kategori()
	{
		$level = $this->session->userdata('ap_level');
		if ($level == 'admin' or $level == 'inventory') {
			if ($_POST) {
				$this->load->library('form_validation');
				$this->form_validation->set_rules('kategori', 'Kategori', 'trim|required|max_length[40]|alpha_numeric_spaces');
				$this->form_validation->set_message('required', '%s harus diisi !');
				$this->form_validation->set_message('alpha_numeric_spaces', '%s Harus huruf / angka !');

				if ($this->form_validation->run() == TRUE) {
					$this->load->model('m_kategori_barang');
					$kategori 	= $this->input->post('kategori');
					$insert 	= $this->m_kategori_barang->tambah_kategori($kategori);
					if ($insert) {
						echo json_encode(array(
							'status' => 1,
							'pesan' => "<div class='alert alert-success'><i class='fa fa-check'></i> <b>" . $kategori . "</b> berhasil ditambahkan.</div>"
						));
					} else {
						$this->query_error();
					}
				} else {
					$this->input_error();
				}
			} else {
				$this->load->view('barang/kategori/kategori_tambah');
			}
		}
	}

	public function hapus_kategori($id_kategori_barang)
	{
		$level = $this->session->userdata('ap_level');
		if ($level == 'admin' or $level == 'inventory') {
			if ($this->input->is_ajax_request()) {
				$this->load->model('m_kategori_barang');
				$hapus = $this->m_kategori_barang->hapus_kategori($id_kategori_barang);
				if ($hapus) {
					echo json_encode(array(
						"pesan" => "<font color='green'><i class='fa fa-check'></i> Data berhasil dihapus !</font>
					"
					));
				} else {
					echo json_encode(array(
						"pesan" => "<font color='red'><i class='fa fa-warning'></i> Terjadi kesalahan, coba lagi !</font>
					"
					));
				}
			}
		}
	}

	public function edit_kategori($id_kategori_barang = NULL)
	{
		if (!empty($id_kategori_barang)) {
			$level = $this->session->userdata('ap_level');
			if ($level == 'admin' or $level == 'inventory') {
				if ($this->input->is_ajax_request()) {
					$this->load->model('m_kategori_barang');

					if ($_POST) {
						$this->load->library('form_validation');
						$this->form_validation->set_rules('kategori', 'Kategori', 'trim|required|max_length[40]|alpha_numeric_spaces');
						$this->form_validation->set_message('required', '%s harus diisi !');
						$this->form_validation->set_message('alpha_numeric_spaces', '%s Harus huruf / angka !');

						if ($this->form_validation->run() == TRUE) {
							$kategori 	= $this->input->post('kategori');
							$insert 	= $this->m_kategori_barang->update_kategori($id_kategori_barang, $kategori);
							if ($insert) {
								echo json_encode(array(
									'status' => 1,
									'pesan' => "<div class='alert alert-success'><i class='fa fa-check'></i> Data berhasil diupdate.</div>"
								));
							} else {
								$this->query_error();
							}
						} else {
							$this->input_error();
						}
					} else {
						$dt['kategori'] = $this->m_kategori_barang->get_baris($id_kategori_barang)->row();
						$this->load->view('barang/kategori/kategori_edit', $dt);
					}
				}
			}
		}
	}

	public function cek_stok()
	{
		if ($this->input->is_ajax_request()) {
			$this->load->model('m_barang');
			$kode = $this->input->post('kode_barang');
			$stok = $this->input->post('stok');

			$get_stok = $this->m_barang->get_stok($kode);
			if ($stok > $get_stok->row()->total_stok) {
				echo json_encode(array('status' => 0, 'pesan' => "Stok untuk <b>" . $get_stok->row()->nama_barang . "</b> saat ini hanya tersisa <b>" . $get_stok->row()->total_stok . "</b> !"));
			} else {
				echo json_encode(array('status' => 1));
			}
		}
	}

	public function do_upload()
	{
		$config['upload_path']          = './assets/img/barang/';
		$config['allowed_types']        = 'gif|jpg|png';
		$config['max_size']             = 100;
		$config['max_width']            = 1024;
		$config['max_height']           = 768;

		$this->load->library('upload', $config);
		if (!$this->upload->do_upload('userfile')) {
			$error = array('error' => $this->upload->display_errors());
			var_dump($error);
			$this->load->view('upload_form', $error);
		} else {
			$data = array('upload_data' => $this->upload->data());
			var_dump($data);
			$this->load->view('upload_success', $data);
		}
	}
}
