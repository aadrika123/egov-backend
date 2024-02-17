CREATE OR REPLACE FUNCTION public.get_ulb_prop_reports(c_ulb_id bigint,c_fyear text ,c_from_date date,c_upto_date date)
    RETURNS TABLE(
		fyear text,
		ulb_id int,c_ulb_name text,
        c_total_props int,
        c_total_assessed_props int,
        c_total_owned_props int,c_total_rented_props int,c_total_mixed_owned_props int,

        c_current_cash_payment numeric, c_current_cheque_payment numeric, 
        c_current_dd_payment numeric, c_current_card_payment numeric, c_current_neft_payment numeric, 
        c_current_rtgs_payment numeric , c_current_Online_payment numeric , c_current_isure_payment numeric,

        c_total_vacant_land int, 
        c_null_prop_data int ,
        c_null_floor_data int,

        c_prop_current_demand numeric, c_prop_arrear_demand numeric , c_prop_total_demand numeric,

        c_total_residential_props  int , c_total_commercial_props   int , c_total_govt_props int ,
        c_total_industrial_props int ,  c_total_religious_props int , c_total_trust_props int , c_total_mixed_props int,

        c_zone_a_name  text , c_zone_a_prop_total_hh  int , c_zone_a_prop_total_amount  numeric,
        c_zone_b_name text ,  c_zone_b_prop_total_hh int , c_zone_b_prop_total_amount numeric ,

        c_top_transaction_first_ward_no text, c_top_transaction_sec_ward_no text, c_top_transaction_third_ward_no text, 
        c_top_transaction_forth_ward_no text, c_top_transaction_fifth_ward_no text,
        c_top_transaction_first_ward_count text, c_top_transaction_sec_ward_count text, c_top_transaction_third_ward_count text, 
        c_top_transaction_forth_ward_count text, c_top_transaction_fifth_ward_count text,
        c_top_transaction_first_ward_amt text, c_top_transaction_sec_ward_amt text, c_top_transaction_third_ward_amt text, 
        c_top_transaction_forth_ward_amt text, c_top_transaction_fifth_ward_amt text,

        c_top_saf_first_ward_no text, c_top_saf_sec_ward_no text, c_top_saf_third_ward_no text, 
        c_top_saf_forth_ward_no text, c_top_saf_fifth_ward_no text,
        c_top_saf_first_ward_count text, c_top_saf_sec_ward_count text, c_top_saf_third_ward_count text, 
        c_top_saf_forth_ward_count text, c_top_saf_fifth_ward_count text,

        c_defaulter_first_ward_no text , c_defaulter_sec_ward_no text , c_defaulter_third_ward_no text ,
        c_defaulter_forth_ward_no text , c_defaulter_fifth_ward_no text , 
        c_defaulter_first_ward_prop_cnt text , c_defaulter_sec_ward_prop_cnt text , c_defaulter_third_ward_prop_cnt text , 
        c_defaulter_forth_ward_prop_cnt  text , c_defaulter_fifth_ward_prop_cnt text ,
        c_defaulter_first_unpaid_amount text , c_defaulter_sec_unpaid_amount text , c_defaulter_third_unpaid_amount text , 
        c_defaulter_forth_unpaid_amount text , c_defaulter_fifth_unpaid_amount text , 

        c_dcb_prop_current_demand NUMERIC , c_dcb_old_demands NUMERIC , c_dcb_outstanding_of_this_year NUMERIC , c_dcb_current_collection NUMERIC , 
		c_dcb_arrear_collection NUMERIC , c_dcb_prop_current_collection_efficiency NUMERIC, 
        c_dcb_prop_arrear_collection_efficiency NUMERIC,

        c_total_user INT , c_supper_admin_count INT , c_admin_count INT , 
        c_project_manager_count INT , c_tl_count INT , c_tc_count INT , 
        c_da_count INT , c_utc_count INT , c_jsk_count INT ,
        c_si_count INT , c_eo_count INT , c_bo_count INT , c_je_count INT , 
        c_sh_count INT , c_ae_count INT , c_td_count INT , c_ac_count INT , 
        c_pmu_count INT , c_ach_count INT , c_ro_count INT , c_ctm_count INT ,
        c_acr_count INT , c_cceo_count INT , c_mis_count INT , c_amo_count INT ,

        c_total_property INT , c_total_demand_property INT , c_total_demand NUMERIC , c_total_unpaid_property INT , c_total_paid_property INT , 
		c_total_current_paid_property INT
	) 
    LANGUAGE 'plpgsql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS $BODY$
declare
   repots record;
begin   
   return QUERY 
   select  c_fyear,id as ulb_id ,ulb_name ,
		total_props ,
		total_assessed_props ,
		total_owned_props ,total_rented_props ,total_mixed_owned_props ,

		current_cash_payment , current_cheque_payment , 
		current_dd_payment , current_card_payment , current_neft_payment , 
		current_rtgs_payment  , current_Online_payment  , current_isure_payment ,

		total_vacant_land , 
		null_prop_data  ,
		null_floor_data ,

		prop_current_demand , prop_arrear_demand  , prop_total_demand ,

		total_residential_props   , total_commercial_props    , total_govt_props  ,
		total_industrial_props  ,  total_religious_props  , total_trust_props  , total_mixed_props ,

		zone_a_name   , zone_a_prop_total_hh   , zone_a_prop_total_amount  ,
		zone_b_name  ,  zone_b_prop_total_hh  , zone_b_prop_total_amount  ,

		top_transaction_first_ward_no , top_transaction_sec_ward_no , top_transaction_third_ward_no , 
		top_transaction_forth_ward_no , top_transaction_fifth_ward_no ,
		top_transaction_first_ward_count , top_transaction_sec_ward_count , top_transaction_third_ward_count , 
		top_transaction_forth_ward_count , top_transaction_fifth_ward_count ,
		top_transaction_first_ward_amt , top_transaction_sec_ward_amt , top_transaction_third_ward_amt , 
		top_transaction_forth_ward_amt , top_transaction_fifth_ward_amt ,

		top_saf_first_ward_no , top_saf_sec_ward_no , top_saf_third_ward_no , 
		top_saf_forth_ward_no , top_saf_fifth_ward_no ,
		top_saf_first_ward_count , top_saf_sec_ward_count , top_saf_third_ward_count , 
		top_saf_forth_ward_count , top_saf_fifth_ward_count ,

		defaulter_first_ward_no  , defaulter_sec_ward_no  , defaulter_third_ward_no  ,
		defaulter_forth_ward_no  , defaulter_fifth_ward_no  , 
		defaulter_first_ward_prop_cnt  , defaulter_sec_ward_prop_cnt  , defaulter_third_ward_prop_cnt  , 
		defaulter_forth_ward_prop_cnt   , defaulter_fifth_ward_prop_cnt  ,
		defaulter_first_unpaid_amount  , defaulter_sec_unpaid_amount  , defaulter_third_unpaid_amount  , 
		defaulter_forth_unpaid_amount  , defaulter_fifth_unpaid_amount  , 

		dcb_prop_current_demand  , dcb_old_demands  , dcb_outstanding_of_this_year  , dcb_current_collection  , dcb_arrear_collection  , 
		dcb_prop_current_collection_efficiency , 
		dcb_prop_arrear_collection_efficiency ,

		total_user  , supper_admin_count  , admin_count  , 
		project_manager_count  , tl_count  , tc_count  , 
		da_count  , utc_count  , jsk_count  ,
		si_count  , eo_count  , bo_count  , je_count  , 
		sh_count  , ae_count  , td_count  , ac_count  , 
		pmu_count  , ach_count  , ro_count  , ctm_count  ,
		acr_count  , cceo_count  , mis_count  , amo_count  ,

		total_property  , total_demand_property  , total_demand  , total_unpaid_property  , total_paid_property  , total_current_paid_property
	from dblink ('dbname = juidco_property port = 5432 host = localhost user = postgres password = 12345'::text, 
			'WITH  total_props AS (
                SELECT 
                    COUNT(id) AS total_props ,ulb_id
                FROM  prop_properties 
                WHERE  status = 1 
				 AND ulb_id = '||c_ulb_id||'
                group by ulb_id
            ),
            total_assessment AS (
                SELECT 
                    COUNT(*) AS total_assessed_props,
                    ulb_id
                FROM 
                    (
            
                        (
                            SELECT  id, ulb_id 
                            FROM prop_active_safs 
                            WHERE  status = 1  AND ulb_id ='||c_ulb_id||'
				 				AND application_date BETWEEN '''||c_from_date||'''  AND '''||c_upto_date||'''
                        )
                    UNION ALL
                        (
                            SELECT  id, ulb_id 
                            FROM  prop_safs 
                            WHERE  status = 1 AND ulb_id ='||c_ulb_id||'
				 				AND application_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||'''
                        )
            
                    UNION ALL 
                        (
                            SELECT  id, ulb_id 
                            FROM  prop_rejected_safs 
                            WHERE  status = 1  AND ulb_id ='||c_ulb_id||'
				 				AND application_date BETWEEN '''||c_from_date||'''  AND '''||c_upto_date||'''
                        )
            
                ) AS a
                WHERE ulb_id IS NOT NULL
                GROUP BY  ulb_id
            ) ,
            total_occupancy_props AS (
                SELECT  ulb_id, 
                    SUM(
                        CASE WHEN nature = ''owned'' THEN 1 ELSE 0 END
                    ) AS total_owned_props, 
                    SUM(
                        CASE WHEN nature = ''rented'' THEN 1 ELSE 0 END
                    ) AS total_rented_props, 
                    SUM(
                        CASE WHEN nature = ''mixed'' THEN 1 ELSE 0 END
                    ) AS total_mixed_owned_props 
                FROM 
                    (
                        SELECT 
                            ulb_id, CASE WHEN a.cnt = a.owned THEN ''owned'' WHEN a.cnt = a.rented THEN ''rented'' ELSE ''mixed'' END AS nature 
                        FROM 
                            (
                                SELECT 
                                    ulb_id,
                                    COUNT(prop_floors.id) AS cnt, 
                                    SUM(
                                        CASE WHEN occupancy_type_mstr_id = 1 THEN 1 ELSE 0 END
                                    ) AS owned, 
                                    SUM(
                                        CASE WHEN occupancy_type_mstr_id = 2 THEN 1 ELSE 0 END
                                    ) AS rented 
                                FROM 
                                    prop_floors 
                                JOIN 
                                    prop_properties ON prop_properties.id = prop_floors.property_id
                                    AND prop_properties.prop_type_mstr_id <> 4 
                                    AND prop_properties.prop_type_mstr_id IS NOT NULL
                                WHERE 
                                    prop_properties.status = 1 
				 					AND ulb_id ='||c_ulb_id||'
                                GROUP BY 
                                    property_id, ulb_id
                            ) AS a
                    ) AS b
                GROUP BY ulb_id
            ) ,
            total_vacant_land As(
                SELECT COUNT(id) as total_vacant_land,ulb_id
                    FROM prop_properties p 
                    WHERE p.prop_type_mstr_id = 4 
				 		AND ulb_id ='||c_ulb_id||'
                    	AND status = 1 
                    group by ulb_id
            ),
            null_prop_data As(
                select count(p.id) as null_prop_data,ulb_id
                    FROM prop_properties p 
                    WHERE p.prop_type_mstr_id IS NULL AND p.status=1
				 		AND ulb_id ='||c_ulb_id||'
                    group by ulb_id
            ),
            null_floor_data As(
                SELECT count(DISTINCT p.id) as null_floor_data,ulb_id
                    FROM prop_properties p 
                    LEFT JOIN prop_floors f ON f.property_id = p.id AND f.status = 1
                    WHERE p.status = 1 
						AND p.prop_type_mstr_id IS NOT NULL 
						AND p.prop_type_mstr_id <> 4 
						AND f.id IS NULL
				 		AND ulb_id ='||c_ulb_id||'
                    group by ulb_id
            ),
            current_payments AS (                                                
                SELECT ulb_id,
                    SUM(CASE WHEN UPPER(payment_mode)=''CASH'' THEN amount ELSE 0 END) AS current_cash_payment,
                    SUM(CASE WHEN UPPER(payment_mode)=''CHEQUE'' THEN amount ELSE 0 END) AS current_cheque_payment,
                    SUM(CASE WHEN UPPER(payment_mode)=''DD'' THEN amount ELSE 0 END) AS current_dd_payment,
                    SUM(CASE WHEN UPPER(payment_mode)=''CARD'' THEN amount ELSE 0 END) AS current_card_payment,
                    SUM(CASE WHEN UPPER(payment_mode)=''NEFT'' THEN amount ELSE 0 END) AS current_neft_payment,
                    SUM(CASE WHEN UPPER(payment_mode)=''RTGS'' THEN amount ELSE 0 END) AS current_rtgs_payment,
                    SUM(CASE WHEN UPPER(payment_mode)=''ONLINE'' THEN amount ELSE 0 END) AS current_Online_payment,
                    SUM(CASE WHEN UPPER(payment_mode)=''ISURE'' THEN amount ELSE 0 END) AS current_isure_payment
                FROM prop_transactions
                WHERE tran_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||''' 
                    and saf_id is null		
                    AND status = 1
				 	AND ulb_id ='||c_ulb_id||'
				group by ulb_id
            ),
            property_use_type AS(
                                SELECT 
                                    ulb_id,
                                    SUM(CASE WHEN nature = ''residential'' THEN 1 ELSE 0 END) AS total_residential_props, 
                                    SUM(CASE WHEN nature = ''commercial'' THEN 1 ELSE 0 END) AS total_commercial_props,
                                    SUM(CASE WHEN nature = ''govt'' THEN 1 ELSE 0 END) AS total_govt_props ,
                                    SUM(CASE WHEN nature = ''industrial'' THEN 1 ELSE 0 END) AS total_industrial_props ,
                                    SUM(CASE WHEN nature = ''religious'' THEN 1 ELSE 0 END) AS total_religious_props ,
                                    SUM(CASE WHEN nature = ''trust'' THEN 1 ELSE 0 END) AS total_trust_props,
                                    SUM(CASE WHEN nature = ''mixed'' THEN 1 ELSE 0 END) AS total_mixed_props
                                FROM (
                                    SELECT 
                                        ulb_id,
                                        CASE 
                                            WHEN cnt = residential THEN ''residential'' 
                                            WHEN cnt = commercial THEN ''commercial'' 
                                            WHEN cnt = govt THEN ''govt'' 
                                            WHEN cnt = industrial THEN ''industrial'' 
                                            WHEN cnt = religious THEN ''religious''
                                            WHEN cnt = trust THEN ''trust''
                                            ELSE ''mixed'' 
                                        END AS nature 
                                    FROM (
                                        SELECT 
                                            property_id, 
                                            ulb_id,
                                            COUNT(prop_floors.id) AS cnt, 
                                            SUM(CASE WHEN usage_type_mstr_id in (1) THEN 1 ELSE 0 END) AS residential, 
                                            SUM(CASE WHEN usage_type_mstr_id IN (13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,38,39,40,41,42) THEN 1 ELSE 0 END) AS commercial ,
                                            SUM(CASE WHEN usage_type_mstr_id in (7,9) THEN 1 ELSE 0 END) AS govt ,
                                            SUM(CASE WHEN usage_type_mstr_id in (33,34,35,36,37) THEN 1 ELSE 0 END) AS industrial,
                                            SUM(CASE WHEN usage_type_mstr_id = 11 THEN 1 ELSE 0 END) AS religious,
                                            SUM(CASE WHEN usage_type_mstr_id in (43,44,45) THEN 1 ELSE 0 END) AS trust
                                        FROM 
                                            prop_floors 
                                        JOIN prop_properties ON prop_properties.id = prop_floors.property_id
                                        WHERE 
                                            prop_properties.status = 1 
                                            AND prop_properties.prop_type_mstr_id <> 4 
                                            AND prop_properties.prop_type_mstr_id IS NOT NULL
				 							AND ulb_id ='||c_ulb_id||'
                                        GROUP BY 
                                            property_id, ulb_id
                                    ) AS a
                                ) AS b
                                GROUP BY 
                                ulb_id
            ),
            zone_wise_dtd as (				
                SELECT
                    zone_masters.id,
                    ulb_masters.id AS ulb_id,
                    CASE
                        WHEN zone_masters.id = 1 THEN ''Zone 1''
                        WHEN zone_masters.id = 2 THEN ''Zone 2''
                        ELSE ''NA''
                    END AS prop_zone_name,
                    COUNT(DISTINCT prop_properties.id) AS prop_total_hh,
                    SUM(transactions.amount) AS prop_total_amount
                FROM
                    zone_masters
                JOIN
                    prop_properties ON prop_properties.zone_mstr_id = zone_masters.id
                JOIN
                    (
                        SELECT
                            property_id,
                            SUM(amount) AS amount,
                            ulb_id
                        FROM
                            prop_transactions
                        JOIN
                            (
                                SELECT DISTINCT
                                    wf_roleusermaps.user_id AS role_user_id
                                FROM
                                    wf_roles
                                JOIN
                                    wf_roleusermaps ON wf_roleusermaps.wf_role_id = wf_roles.id
                                        AND wf_roleusermaps.is_suspended = FALSE
                                JOIN
                                    wf_workflowrolemaps ON wf_workflowrolemaps.wf_role_id = wf_roleusermaps.wf_role_id
                                        AND wf_workflowrolemaps.is_suspended = FALSE
                                JOIN
                                    wf_workflows ON wf_workflows.id = wf_workflowrolemaps.workflow_id
                                        AND wf_workflows.is_suspended = FALSE
                                JOIN
                                    ulb_masters ON ulb_masters.id = wf_workflows.ulb_id
                                WHERE
                                    wf_roles.is_suspended = FALSE
                                    AND wf_workflows.ulb_id = 2
                                    AND wf_roles.id NOT IN (8, 108)
                                    AND wf_workflows.id IN (3, 4, 5)
                                GROUP BY
                                    wf_roleusermaps.user_id
                                ORDER BY
                                    wf_roleusermaps.user_id
                            ) collector ON prop_transactions.user_id = collector.role_user_id
                        WHERE
                            status IN (1, 2)
                            AND UPPER(payment_mode) != ''ONLINE''
                            AND tran_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||'''
                            AND property_id IS NOT NULL
                        GROUP BY
                            property_id, ulb_id
                    ) transactions ON transactions.property_id = prop_properties.id
                JOIN
                    ulb_masters ON ulb_masters.id = transactions.ulb_id
				 WHERE ulb_masters.id ='||c_ulb_id||'
                GROUP BY
                    zone_masters.id, ulb_masters.id
                ORDER BY
                    zone_masters.id
            ),
            zone_a_dtd as (
                select *
                from zone_wise_dtd
                where id =1
            ),
            zone_b_dtd as (
                select * 
                from zone_wise_dtd
                where id =2
            ),
            zone_dtd_collection as(
                select ulb_masters.id as ulb_id,
                    zone_a_dtd.prop_zone_name as zone_a_name,
                    zone_a_dtd.prop_total_hh as zone_a_prop_total_hh,
                    zone_a_dtd.prop_total_amount as zone_a_prop_total_amount,
            
            
                    zone_b_dtd.prop_zone_name as zone_b_name,
                    zone_b_dtd.prop_total_hh as zone_b_prop_total_hh,
                    zone_b_dtd.prop_total_amount as zone_b_prop_total_amount
            
                from ulb_masters
                left join zone_a_dtd on zone_a_dtd.ulb_id = ulb_masters.id
                left join zone_b_dtd on zone_b_dtd.ulb_id = ulb_masters.id
				WHERE ulb_masters.id = '||c_ulb_id||'
                order by ulb_masters.id
            ),
            top_wards_collections as(
                SELECT ulb_id,(string_to_array(string_agg(top_wards_collections.ward_name::TEXT,'',''),'',''))[1] AS top_transaction_first_ward_no,
                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,'',''),'',''))[2] AS top_transaction_sec_ward_no,
                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,'',''),'',''))[3] AS top_transaction_third_ward_no,
                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,'',''),'',''))[4] AS top_transaction_forth_ward_no,
                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,'',''),'',''))[5] AS top_transaction_fifth_ward_no,
                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,'',''),'',''))[1] AS top_transaction_first_ward_count,
                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,'',''),'',''))[2] AS top_transaction_sec_ward_count,
                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,'',''),'',''))[3] AS top_transaction_third_ward_count,
                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,'',''),'',''))[4] AS top_transaction_forth_ward_count,
                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,'',''),'',''))[5] AS top_transaction_fifth_ward_count,
                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,'',''),'',''))[1] AS top_transaction_first_ward_amt,
                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,'',''),'',''))[2] AS top_transaction_sec_ward_amt,
                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,'',''),'',''))[3] AS top_transaction_third_ward_amt,
                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,'',''),'',''))[4] AS top_transaction_forth_ward_amt,
                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,'',''),'',''))[5] AS top_transaction_fifth_ward_amt
            
                    FROM (
                        SELECT 
                                p.ulb_id,p.ward_mstr_id,
                                SUM(t.amount) AS collected_amt,
                                COUNT(t.id) AS collection_count,
                                u.ward_name
                    
                            FROM prop_transactions t
                            JOIN prop_properties p ON p.id=t.property_id
                            JOIN ulb_ward_masters u ON u.id=p.ward_mstr_id
                            WHERE t.tran_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||'''
				 				AND p.ulb_id ='||c_ulb_id||'
                            GROUP BY p.ward_mstr_id,u.ward_name, p.ulb_id
                            ORDER BY collection_count DESC 
                        
                    ) AS top_wards_collections
                    group by ulb_id
            ),
            top_area_safs As (
                    SELECT 
                        ulb_id,
                        (string_to_array(string_agg(top_area_safs.ward_name::TEXT,'',''),'',''))[1] AS top_saf_first_ward_no,
                        (string_to_array(string_agg(top_area_safs.ward_name::TEXT,'',''),'',''))[2] AS top_saf_sec_ward_no,
                        (string_to_array(string_agg(top_area_safs.ward_name::TEXT,'',''),'',''))[3] AS top_saf_third_ward_no,
                        (string_to_array(string_agg(top_area_safs.ward_name::TEXT,'',''),'',''))[4] AS top_saf_forth_ward_no,
                        (string_to_array(string_agg(top_area_safs.ward_name::TEXT,'',''),'',''))[5] AS top_saf_fifth_ward_no,
                        (string_to_array(string_agg(top_area_safs.application_count::TEXT,'',''),'',''))[1] AS top_saf_first_ward_count,
                        (string_to_array(string_agg(top_area_safs.application_count::TEXT,'',''),'',''))[2] AS top_saf_sec_ward_count,
                        (string_to_array(string_agg(top_area_safs.application_count::TEXT,'',''),'',''))[3] AS top_saf_third_ward_count,
                        (string_to_array(string_agg(top_area_safs.application_count::TEXT,'',''),'',''))[4] AS top_saf_forth_ward_count,
                        (string_to_array(string_agg(top_area_safs.application_count::TEXT,'',''),'',''))[5] AS top_saf_fifth_ward_count
                    FROM (
                        SELECT 
                            top_areas_safs.ward_mstr_id,
                            SUM(top_areas_safs.application_count) AS application_count,
                            u.ward_name,
                        top_areas_safs.ulb_id
                        FROM (
                            SELECT 
                                COUNT(id) AS application_count,
                                ward_mstr_id,
                                ulb_id
                            FROM prop_active_safs
                            WHERE application_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||'''
                        		AND ulb_id ='||c_ulb_id||'
                            GROUP BY ward_mstr_id, ulb_id

                            UNION ALL 

                            SELECT 
                                COUNT(id) AS application_count,
                                ward_mstr_id,
                                ulb_id
                            FROM prop_safs
                            WHERE application_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||'''
				 				AND ulb_id ='||c_ulb_id||'
                            GROUP BY ward_mstr_id, ulb_id

                            UNION ALL 

                            SELECT 
                                COUNT(id) AS application_count,
                                ward_mstr_id,
                                ulb_id
                            FROM prop_rejected_safs
                            WHERE application_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||'''
				 				AND ulb_id ='||c_ulb_id||'
                        
                            GROUP BY ward_mstr_id, ulb_id
                        ) AS top_areas_safs
                        JOIN ulb_ward_masters u ON u.id=top_areas_safs.ward_mstr_id
                        GROUP BY top_areas_safs.ward_mstr_id, u.ward_name, top_areas_safs.ulb_id 
                        ORDER BY application_count DESC 
                    
                    ) AS top_area_safs
                GROUP BY ulb_id
            ),
            area_wise_defaulter AS(
                SELECT  ulb_id,
                (string_to_array(string_agg(a.ward_name::TEXT,'',''),'',''))[1] AS defaulter_first_ward_no,
                (string_to_array(string_agg(a.ward_name::TEXT,'',''),'',''))[2] AS defaulter_sec_ward_no,
                (string_to_array(string_agg(a.ward_name::TEXT,'',''),'',''))[3] AS defaulter_third_ward_no,
                (string_to_array(string_agg(a.ward_name::TEXT,'',''),'',''))[4] AS defaulter_forth_ward_no,
                (string_to_array(string_agg(a.ward_name::TEXT,'',''),'',''))[5] AS defaulter_fifth_ward_no,
                (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,'',''),'',''))[1] AS defaulter_first_ward_prop_cnt,
                (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,'',''),'',''))[2] AS defaulter_sec_ward_prop_cnt,
                (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,'',''),'',''))[3] AS defaulter_third_ward_prop_cnt,
                (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,'',''),'',''))[4] AS defaulter_forth_ward_prop_cnt,
                (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,'',''),'',''))[5] AS defaulter_fifth_ward_prop_cnt,
                (string_to_array(string_agg(a.unpaid_amount::TEXT,'',''),'',''))[1] AS defaulter_first_unpaid_amount,
                (string_to_array(string_agg(a.unpaid_amount::TEXT,'',''),'',''))[2] AS defaulter_sec_unpaid_amount,
                (string_to_array(string_agg(a.unpaid_amount::TEXT,'',''),'',''))[3] AS defaulter_third_unpaid_amount,
                (string_to_array(string_agg(a.unpaid_amount::TEXT,'',''),'',''))[4] AS defaulter_forth_unpaid_amount,
                (string_to_array(string_agg(a.unpaid_amount::TEXT,'',''),'',''))[5] AS defaulter_fifth_unpaid_amount

                FROM 
                    (
                        SELECT 
                            COUNT(a.property_id) AS defaulter_property_cnt,
                            w.ward_name,p.ulb_id,
                            SUM(a.unpaid_amt) AS unpaid_amount

                            FROM 
                                (
                                    SELECT
                                            property_id,
                                            COUNT(id) AS demand_cnt,
                                            SUM(CASE WHEN paid_status=1 THEN 1 ELSE 0 END) AS paid_count,
                                            SUM(CASE WHEN paid_status=0 THEN 1 ELSE 0 END) AS unpaid_count,
                                            SUM(CASE WHEN paid_status=0 THEN balance ELSE 0 END) AS unpaid_amt

                                    FROM prop_demands
                                    WHERE fyear='''||c_fyear||'''								
                                    AND status=1 
                                    GROUP BY property_id
                                    ORDER BY property_id
                            ) a 
                            JOIN prop_properties p ON p.id=a.property_id
                            JOIN ulb_ward_masters w ON w.id=p.ward_mstr_id
                                
                            WHERE a.demand_cnt=a.unpaid_count 
                            AND p.status=1 AND p.ulb_id ='||c_ulb_id||'
                                
                            GROUP BY w.ward_name ,p.ulb_id
                                
                            ORDER BY defaulter_property_cnt DESC 

                    ) a
                    group by ulb_id
            ),                        
            demand AS (
                SELECT ulb_id,
                    SUM(
                        CASE WHEN prop_demands.due_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||''' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                            ELSE 0
                            END
                        ) AS prop_current_demand,
                    SUM(
                        CASE WHEN prop_demands.due_date<'''||c_from_date||''' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                            ELSE 0
                            END
                        ) AS prop_arrear_demand,
                    SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS prop_total_demand
                    FROM prop_demands
                    where status = 1 AND ulb_id ='||c_ulb_id||'
                    group by ulb_id
            ),
            collection as (	
                SELECT prop_demands.ulb_id,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '''||c_from_date||''' AND '''||c_upto_date||''' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                    ELSE 0
                                    END
                        ) AS current_collection,
                        SUM(
                            cASe when prop_demands.due_date <'''||c_from_date||''' then COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS total_collection 
                FROM prop_demands
                JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                    AND prop_tran_dtls.prop_demand_id is not null 
                JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                    AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                WHERE prop_demands.status =1 
                    AND prop_transactions.tran_date  BETWEEN '''||c_from_date||''' AND '''||c_upto_date||'''
                    --AND prop_demands.due_date<='''||c_upto_date||'''
				 	AND prop_demands.ulb_id ='||c_ulb_id||'
                GROUP BY prop_demands.ulb_id
            ),
            prive_collection as(
                SELECT prop_demands.ulb_id,
                        SUM(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) AS total_prev_collection
                FROM prop_demands
                JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 	
                JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 				
                WHERE prop_demands.status =1 AND prop_tran_dtls.prop_demand_id is not null 
                    AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    AND prop_transactions.tran_date<'''||c_from_date||'''
				 	AND prop_demands.ulb_id ='||c_ulb_id||'
                GROUP BY prop_demands.ulb_id
            ),
            dcb as(
                select ulb_masters.id as ulb_id,
                    demand.prop_current_demand,demand.prop_arrear_demand as old_demands,
                    (Coalesce(demand.prop_arrear_demand,0) - Coalesce(prive_collection.total_prev_collection,0)) as outstanding_of_this_year,
                    collection.current_collection , collection.arrear_collection,
                CASE 
                    WHEN SUM(COALESCE(demand.prop_current_demand, 0)) > 0 
                    THEN (SUM(COALESCE(collection.current_collection, 0)) / SUM(COALESCE(demand.prop_current_demand, 0))) * 100
                    ELSE 0
                END AS prop_current_collection_efficiency,
                CASE 
                    WHEN (SUM(Coalesce(demand.prop_arrear_demand,0) - Coalesce(prive_collection.total_prev_collection,0))) > 0 
                    THEN (SUM(COALESCE(collection.arrear_collection, 0)) / ((SUM(Coalesce(demand.prop_arrear_demand,0) - Coalesce(prive_collection.total_prev_collection,0)))) * 100)
                    ELSE 0
                END AS prop_arrear_collection_efficiency
                from ulb_masters
                left join demand on demand.ulb_id = ulb_masters.id
                left join collection on collection.ulb_id = ulb_masters.id
                left join prive_collection on prive_collection.ulb_id = ulb_masters.id
				 WHERE ulb_masters.id ='||c_ulb_id||'
                GROUP BY  ulb_masters.id,
                    demand.prop_current_demand,
                    demand.prop_arrear_demand,
                    (COALESCE(demand.prop_arrear_demand, 0) - COALESCE(prive_collection.total_prev_collection, 0)),
                    collection.current_collection,
                    collection.arrear_collection
            ) ,
            final_ulb_role_wise_users_count as (
                select ulb_masters.id as ulb_id, ulb_masters.ulb_name,
                    count(distinct (users.id)) as total_user,
                    count(case when  wf_roles.id = 1 then users.id end)as supper_admin_count,
                    count(case when  wf_roles.id = 2 then users.id end)as admin_count,
                    count(case when  wf_roles.id = 3 then users.id end)as project_manager_count,
                    count(case when  wf_roles.id = 4 then users.id end)as tl_count,
                    count(case when  wf_roles.id = 5 then users.id end)as tc_count,
                    count(case when  wf_roles.id = 6 then users.id end)as da_count,
                    count(case when  wf_roles.id = 7 then users.id end)as utc_count,
                    count(case when  wf_roles.id = 8 then users.id end)as jsk_count,
                    count(case when  wf_roles.id = 9 then users.id end)as si_count,
                    count(case when  wf_roles.id = 10 then users.id end)as eo_count,
                    count(case when  wf_roles.id = 11 then users.id end)as bo_count,
                    count(case when  wf_roles.id = 12 then users.id end)as je_count,
                    count(case when  wf_roles.id = 13 then users.id end)as sh_count,
                    count(case when  wf_roles.id = 14 then users.id end)as ae_count,
                    count(case when  wf_roles.id = 15 then users.id end)as td_count,
                    count(case when  wf_roles.id = 16 then users.id end)as ac_count,
                    count(case when  wf_roles.id = 17 then users.id end)as pmu_count,
                    count(case when  wf_roles.id = 18 then users.id end)as ach_count,
                    count(case when  wf_roles.id = 19 then users.id end)as ro_count,
                    count(case when  wf_roles.id = 20 then users.id end)as ctm_count,
                    count(case when  wf_roles.id = 21 then users.id end)as acr_count,
                    count(case when  wf_roles.id = 22 then users.id end)as cceo_count,
                    count(case when  wf_roles.id = 23 then users.id end)as mis_count,
                    count(case when  wf_roles.id = 24 then users.id end)as amo_count
                from ulb_masters
                left join users on users.ulb_id = ulb_masters.id and users.suspended = false
                left join wf_roleusermaps on wf_roleusermaps.user_id = users.id and wf_roleusermaps.is_suspended = false
                left join wf_roles on wf_roles.id = wf_roleusermaps.wf_role_id and wf_roles.is_suspended = false
				WHERE ulb_masters.id ='||c_ulb_id||'
                group by ulb_masters.id,ulb_masters.ulb_name
            ),
            prop_demand as (
                select distinct property_id,sum(COALESCE(prop_demands.amount,0) -COALESCE(prop_demands.adjust_amt,0)) as total_demand,
                    count(id) as total_demand_count,
                    count(case when paid_status =1 then id else null end) paid_demand_count,
                    count(case when paid_status !=1 then id else null end) unpaid_demand_count,
                    count(case when fyear = '''||c_fyear||''' then id else null end) as current_demand_count,
                    count(case when fyear = '''||c_fyear||''' and paid_status = 1 then id else null end) as current_demand_paid_count,
                    count(case when fyear = '''||c_fyear||''' and paid_status != 1 then id else null end) as current_demand_unpaid_count
                from prop_demands
                where prop_demands.status =1
                group by property_id
            ),
            propertis as (
                select prop_properties.ulb_id, count(prop_properties.id) as total_property,
                        count(prop_demand.property_id) as total_demand_property,
                        sum(prop_demand.total_demand) as total_demand,
                    count (
                            case when prop_demand.property_id is null or prop_demand.total_demand_count = prop_demand.unpaid_demand_count 
                                then prop_properties.id 
                                else null end
                    ) as total_unpaid_property,
                    count (
                            case when prop_demand.property_id is not null and prop_demand.total_demand_count = prop_demand.paid_demand_count 
                                then prop_properties.id 
                                else null end
                    ) as total_paid_property,
                    count (
                            case when prop_demand.property_id is not null and prop_demand.current_demand_count = prop_demand.current_demand_paid_count 
                                then prop_properties.id 
                                else null end
                    ) as total_current_paid_property
                from prop_properties
                left join prop_demand on prop_demand.property_id = prop_properties.id	
                where prop_properties.status =1 AND prop_properties.ulb_id ='||c_ulb_id||'
                group by prop_properties.ulb_id
            ),
            prop_tran as(
                select ulb_masters.id as ulb_id,ulb_masters.ulb_name,
                    propertis.total_property,propertis.total_demand_property, propertis.total_demand, propertis.total_unpaid_property,
                    propertis.total_paid_property, propertis.total_current_paid_property
                from ulb_masters
                left join propertis on propertis.ulb_id = ulb_masters.id
				 WHERE ulb_masters.id ='||c_ulb_id||'
            )
                        
            select ulb_masters.id,ulb_masters.ulb_name,
                total_props.total_props,
                total_assessment.total_assessed_props,
                total_occupancy_props.total_owned_props,total_occupancy_props.total_rented_props, total_occupancy_props.total_mixed_owned_props ,

                current_payments.current_cash_payment , current_payments.current_cheque_payment, 
                current_payments.current_dd_payment, current_payments.current_card_payment, current_payments.current_neft_payment, 
                current_payments.current_rtgs_payment, current_payments.current_Online_payment, current_payments.current_isure_payment,

                total_vacant_land.total_vacant_land ,
                null_prop_data.null_prop_data,
                null_floor_data.null_floor_data , 

                demand.prop_current_demand , demand.prop_arrear_demand, demand.prop_total_demand ,

                property_use_type.total_residential_props , property_use_type.total_commercial_props  , property_use_type.total_govt_props ,
                property_use_type.total_industrial_props,  property_use_type.total_religious_props, property_use_type.total_trust_props, 
                property_use_type.total_mixed_props ,

                zone_dtd_collection.zone_a_name   , zone_dtd_collection.zone_a_prop_total_hh    , zone_dtd_collection.zone_a_prop_total_amount  ,
                zone_dtd_collection.zone_b_name  ,  zone_dtd_collection.zone_b_prop_total_hh  , zone_dtd_collection.zone_b_prop_total_amount  ,

                top_wards_collections.top_transaction_first_ward_no, top_wards_collections.top_transaction_sec_ward_no, top_wards_collections.top_transaction_third_ward_no, 
                top_wards_collections.top_transaction_forth_ward_no, top_wards_collections.top_transaction_fifth_ward_no,
                top_wards_collections.top_transaction_first_ward_count, top_wards_collections.top_transaction_sec_ward_count, top_wards_collections.top_transaction_third_ward_count, 
                top_wards_collections.top_transaction_forth_ward_count, top_wards_collections.top_transaction_fifth_ward_count,
                top_wards_collections.top_transaction_first_ward_amt, top_wards_collections.top_transaction_sec_ward_amt, top_wards_collections.top_transaction_third_ward_amt, 
                top_wards_collections.top_transaction_forth_ward_amt, top_wards_collections.top_transaction_fifth_ward_amt,

                top_area_safs.top_saf_first_ward_no, top_area_safs.top_saf_sec_ward_no, top_area_safs.top_saf_third_ward_no, 
                top_area_safs.top_saf_forth_ward_no, top_area_safs.top_saf_fifth_ward_no,
                top_area_safs.top_saf_first_ward_count, top_area_safs.top_saf_sec_ward_count, top_area_safs.top_saf_third_ward_count, 
                top_area_safs.top_saf_forth_ward_count, top_area_safs.top_saf_fifth_ward_count,
                
                area_wise_defaulter.defaulter_first_ward_no, area_wise_defaulter.defaulter_sec_ward_no, area_wise_defaulter.defaulter_third_ward_no,
                area_wise_defaulter.defaulter_forth_ward_no, area_wise_defaulter.defaulter_fifth_ward_no, 
                area_wise_defaulter.defaulter_first_ward_prop_cnt, area_wise_defaulter.defaulter_sec_ward_prop_cnt, area_wise_defaulter.defaulter_third_ward_prop_cnt, 
                area_wise_defaulter.defaulter_forth_ward_prop_cnt , area_wise_defaulter.defaulter_fifth_ward_prop_cnt,
                area_wise_defaulter.defaulter_first_unpaid_amount, area_wise_defaulter.defaulter_sec_unpaid_amount, area_wise_defaulter.defaulter_third_unpaid_amount, 
                area_wise_defaulter.defaulter_forth_unpaid_amount, area_wise_defaulter.defaulter_fifth_unpaid_amount,  
					  
                dcb.prop_current_demand AS dcb_prop_current_demand, dcb.old_demands AS dcb_old_demands, dcb.outstanding_of_this_year AS dcb_outstanding_of_this_year,
			    dcb.current_collection AS dcb_current_collection, dcb.arrear_collection AS dcb_arrear_collection, 
				dcb.prop_current_collection_efficiency AS dcb_prop_current_collection_efficiency, 
				dcb.prop_arrear_collection_efficiency AS dcb_prop_arrear_collection_efficiency, 
                
                final_ulb_role_wise_users_count.total_user, final_ulb_role_wise_users_count.supper_admin_count, final_ulb_role_wise_users_count.admin_count, 
                final_ulb_role_wise_users_count.project_manager_count, final_ulb_role_wise_users_count.tl_count, final_ulb_role_wise_users_count.tc_count, 
                final_ulb_role_wise_users_count.da_count, final_ulb_role_wise_users_count.utc_count, final_ulb_role_wise_users_count.jsk_count,
                final_ulb_role_wise_users_count.si_count, final_ulb_role_wise_users_count.eo_count, final_ulb_role_wise_users_count.bo_count, final_ulb_role_wise_users_count.je_count, 
                final_ulb_role_wise_users_count.sh_count, final_ulb_role_wise_users_count.ae_count, final_ulb_role_wise_users_count.td_count, final_ulb_role_wise_users_count.ac_count, 
                final_ulb_role_wise_users_count.pmu_count, final_ulb_role_wise_users_count.ach_count, final_ulb_role_wise_users_count.ro_count, final_ulb_role_wise_users_count.ctm_count,
                final_ulb_role_wise_users_count.acr_count, final_ulb_role_wise_users_count.cceo_count, final_ulb_role_wise_users_count.mis_count, final_ulb_role_wise_users_count.amo_count, 
				 
                prop_tran.total_property, prop_tran.total_demand_property, prop_tran.total_demand, prop_tran.total_unpaid_property, prop_tran.total_paid_property, prop_tran.total_current_paid_property

            from ulb_masters
            left join total_props on total_props.ulb_id = ulb_masters.id
            left join total_assessment on total_assessment.ulb_id = ulb_masters.id
            left join total_occupancy_props on total_occupancy_props.ulb_id = ulb_masters.id
            left join current_payments on current_payments.ulb_id = ulb_masters.id
            left join total_vacant_land on total_vacant_land.ulb_id = ulb_masters.id
            left join null_prop_data on null_prop_data.ulb_id = ulb_masters.id
            left join null_floor_data on null_floor_data.ulb_id = ulb_masters.id       
            left join demand on demand.ulb_id = ulb_masters.id       
            left join property_use_type on property_use_type.ulb_id = ulb_masters.id 
            left join zone_dtd_collection on zone_dtd_collection.ulb_id = ulb_masters.id 
            left join top_wards_collections on top_wards_collections.ulb_id = ulb_masters.id 
            left join top_area_safs on top_area_safs.ulb_id = ulb_masters.id 
            left join area_wise_defaulter on area_wise_defaulter.ulb_id = ulb_masters.id 
            left join dcb on dcb.ulb_id = ulb_masters.id
            left join final_ulb_role_wise_users_count on final_ulb_role_wise_users_count.ulb_id = ulb_masters.id
            left join prop_tran on prop_tran.ulb_id = ulb_masters.id
            where ulb_masters.id ='||c_ulb_id||''::text
	   )
	   ropts(
	   	id int,ulb_name text,
        total_props int,
        total_assessed_props int,
        total_owned_props int,total_rented_props int,total_mixed_owned_props int,

        current_cash_payment numeric, current_cheque_payment numeric, 
        current_dd_payment numeric, current_card_payment numeric, current_neft_payment numeric, 
        current_rtgs_payment numeric , current_Online_payment numeric , current_isure_payment numeric,

        total_vacant_land int, 
        null_prop_data int ,
        null_floor_data int,

        prop_current_demand numeric, prop_arrear_demand numeric , prop_total_demand numeric,

        total_residential_props  int , total_commercial_props   int , total_govt_props int ,
        total_industrial_props int ,  total_religious_props int , total_trust_props int , total_mixed_props int,

        zone_a_name  text , zone_a_prop_total_hh  int , zone_a_prop_total_amount  numeric,
        zone_b_name text ,  zone_b_prop_total_hh int , zone_b_prop_total_amount numeric ,

        top_transaction_first_ward_no text, top_transaction_sec_ward_no text, top_transaction_third_ward_no text, 
        top_transaction_forth_ward_no text, top_transaction_fifth_ward_no text,
        top_transaction_first_ward_count text, top_transaction_sec_ward_count text, top_transaction_third_ward_count text, 
        top_transaction_forth_ward_count text, top_transaction_fifth_ward_count text,
        top_transaction_first_ward_amt text, top_transaction_sec_ward_amt text, top_transaction_third_ward_amt text, 
        top_transaction_forth_ward_amt text, top_transaction_fifth_ward_amt text,

        top_saf_first_ward_no text, top_saf_sec_ward_no text, top_saf_third_ward_no text, 
        top_saf_forth_ward_no text, top_saf_fifth_ward_no text,
        top_saf_first_ward_count text, top_saf_sec_ward_count text, top_saf_third_ward_count text, 
        top_saf_forth_ward_count text, top_saf_fifth_ward_count text,

        defaulter_first_ward_no text , defaulter_sec_ward_no text , defaulter_third_ward_no text ,
        defaulter_forth_ward_no text , defaulter_fifth_ward_no text , 
        defaulter_first_ward_prop_cnt text , defaulter_sec_ward_prop_cnt text , defaulter_third_ward_prop_cnt text , 
        defaulter_forth_ward_prop_cnt  text , defaulter_fifth_ward_prop_cnt text ,
        defaulter_first_unpaid_amount text , defaulter_sec_unpaid_amount text , defaulter_third_unpaid_amount text , 
        defaulter_forth_unpaid_amount text , defaulter_fifth_unpaid_amount text , 

        dcb_prop_current_demand NUMERIC , dcb_old_demands NUMERIC , dcb_outstanding_of_this_year NUMERIC , dcb_current_collection NUMERIC , 
		dcb_arrear_collection NUMERIC , dcb_prop_current_collection_efficiency NUMERIC, 
        dcb_prop_arrear_collection_efficiency NUMERIC,

        total_user INT , supper_admin_count INT , admin_count INT , 
        project_manager_count INT , tl_count INT , tc_count INT , 
        da_count INT , utc_count INT , jsk_count INT ,
        si_count INT , eo_count INT , bo_count INT , je_count INT , 
        sh_count INT , ae_count INT , td_count INT , ac_count INT , 
        pmu_count INT , ach_count INT , ro_count INT , ctm_count INT ,
        acr_count INT , cceo_count INT , mis_count INT , amo_count INT ,

        total_property INT , total_demand_property INT , total_demand NUMERIC , total_unpaid_property INT , total_paid_property INT , total_current_paid_property INT
	   )
   ;
end;
$BODY$;