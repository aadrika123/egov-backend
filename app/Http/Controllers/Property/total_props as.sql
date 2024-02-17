total_props as (
                            SELECT 
                                COUNT(id) AS total_props ,ulb_id
                            FROM  prop_properties 
                            WHERE  status = 1 
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
                                        WHERE  status = 1  AND application_date BETWEEN '$currentfyStartDate'  AND '$currentfyEndDate'
                                    )
                                UNION ALL
                                    (
                                        SELECT  id, ulb_id 
                                        FROM  prop_safs 
                                        WHERE  status = 1 AND application_date BETWEEN '$currentfyStartDate' AND '$currentfyEndDate'
                                    )
                        
                                UNION ALL 
                                    (
                                        SELECT  id, ulb_id 
                                        FROM  prop_rejected_safs 
                                        WHERE  status = 1  AND application_date BETWEEN '$currentfyStartDate'  AND '$currentfyEndDate'
                                    )
                        
                            ) AS a
                            WHERE ulb_id IS NOT NULL
                            GROUP BY  ulb_id
                        ) ,
                        total_occupancy_props AS (
                            SELECT  ulb_id, 
                                SUM(
                                    CASE WHEN nature = 'owned' THEN 1 ELSE 0 END
                                ) AS total_owned_props, 
                                SUM(
                                    CASE WHEN nature = 'rented' THEN 1 ELSE 0 END
                                ) AS total_rented_props, 
                                SUM(
                                    CASE WHEN nature = 'mixed' THEN 1 ELSE 0 END
                                ) AS total_mixed_owned_props 
                            FROM 
                                (
                                    SELECT 
                                        ulb_id, CASE WHEN a.cnt = a.owned THEN 'owned' WHEN a.cnt = a.rented THEN 'rented' ELSE 'mixed' END AS nature 
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
                                AND status = 1 
                                group by ulb_id

                        ),
                        null_prop_data As(
                            select count(p.id) as null_prop_data,ulb_id
                                FROM prop_properties p 
                                WHERE p.prop_type_mstr_id IS NULL AND p.status=1
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
                                group by ulb_id

                        ),
                        current_payments AS (
                                                
                            SELECT ulb_id,
                                SUM(CASE WHEN UPPER(payment_mode)='CASH' THEN amount ELSE 0 END) AS current_cash_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='CHEQUE' THEN amount ELSE 0 END) AS current_cheque_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='DD' THEN amount ELSE 0 END) AS current_dd_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='CARD' THEN amount ELSE 0 END) AS current_card_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='NEFT' THEN amount ELSE 0 END) AS current_neft_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='RTGS' THEN amount ELSE 0 END) AS current_rtgs_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='ONLINE' THEN amount ELSE 0 END) AS current_Online_payment,
                                SUM(CASE WHEN UPPER(payment_mode)='ISURE' THEN amount ELSE 0 END) AS current_isure_payment
                            FROM prop_transactions
                            WHERE tran_date BETWEEN '$currentfyStartDate' AND '$currentfyEndDate' 
                                and saf_id is null		
                                AND status = 1
                                group by ulb_id
                        ),
                        property_use_type AS(
                                            SELECT 
                                                ulb_id,
                                                SUM(CASE WHEN nature = 'residential' THEN 1 ELSE 0 END) AS total_residential_props, 
                                                SUM(CASE WHEN nature = 'commercial' THEN 1 ELSE 0 END) AS total_commercial_props,
                                                SUM(CASE WHEN nature = 'govt' THEN 1 ELSE 0 END) AS total_govt_props ,
                                                SUM(CASE WHEN nature = 'industrial' THEN 1 ELSE 0 END) AS total_industrial_props ,
                                                SUM(CASE WHEN nature = 'religious' THEN 1 ELSE 0 END) AS total_religious_props ,
                                                SUM(CASE WHEN nature = 'trust' THEN 1 ELSE 0 END) AS total_trust_props,
                                                SUM(CASE WHEN nature = 'mixed' THEN 1 ELSE 0 END) AS total_mixed_props
                                            FROM (
                                                SELECT 
                                                    ulb_id,
                                                    CASE 
                                                        WHEN cnt = residential THEN 'residential' 
                                                        WHEN cnt = commercial THEN 'commercial' 
                                                        WHEN cnt = govt THEN 'govt' 
                                                        WHEN cnt = industrial THEN 'industrial' 
                                                        WHEN cnt = religious THEN 'religious'
                                                        WHEN cnt = trust THEN 'trust'
                                                        ELSE 'mixed' 
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
                                    WHEN zone_masters.id = 1 THEN 'Zone 1'
                                    WHEN zone_masters.id = 2 THEN 'Zone 2'
                                    ELSE 'NA'
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
                                        AND UPPER(payment_mode) != 'ONLINE'
                                        AND tran_date BETWEEN '2023-04-01' AND '2024-03-31'
                                        AND property_id IS NOT NULL
                                    GROUP BY
                                        property_id, ulb_id
                                ) transactions ON transactions.property_id = prop_properties.id
                            JOIN
                                ulb_masters ON ulb_masters.id = transactions.ulb_id
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
                            order by ulb_masters.id
                        ),
                        top_wards_collections as(
                             SELECT ulb_id,(string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[1] AS top_transaction_first_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[2] AS top_transaction_sec_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[3] AS top_transaction_third_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[4] AS top_transaction_forth_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.ward_name::TEXT,','),','))[5] AS top_transaction_fifth_ward_no,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[1] AS top_transaction_first_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[2] AS top_transaction_sec_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[3] AS top_transaction_third_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[4] AS top_transaction_forth_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collection_count::TEXT,','),','))[5] AS top_transaction_fifth_ward_count,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[1] AS top_transaction_first_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[2] AS top_transaction_sec_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[3] AS top_transaction_third_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[4] AS top_transaction_forth_ward_amt,
                                    (string_to_array(string_agg(top_wards_collections.collected_amt::TEXT,','),','))[5] AS top_transaction_fifth_ward_amt
                         
                                   FROM (
                                       SELECT 
                                                p.ulb_id,p.ward_mstr_id,
                                               SUM(t.amount) AS collected_amt,
                                               COUNT(t.id) AS collection_count,
                                               u.ward_name
                                 
                                           FROM prop_transactions t
                                           JOIN prop_properties p ON p.id=t.property_id
                                           JOIN ulb_ward_masters u ON u.id=p.ward_mstr_id
                                           WHERE t.tran_date BETWEEN '2023-04-01' AND '2024-03-31'							
                                           GROUP BY p.ward_mstr_id,u.ward_name, p.ulb_id
                                           ORDER BY collection_count DESC 
                                     
                                   ) AS top_wards_collections
                                  group by ulb_id
                         ),
                         top_area_safs As (
                                        SELECT 
                                            ulb_id,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[1] AS top_saf_first_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[2] AS top_saf_sec_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[3] AS top_saf_third_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[4] AS top_saf_forth_ward_no,
                                            (string_to_array(string_agg(top_area_safs.ward_name::TEXT,','),','))[5] AS top_saf_fifth_ward_no,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[1] AS top_saf_first_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[2] AS top_saf_sec_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[3] AS top_saf_third_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[4] AS top_saf_forth_ward_count,
                                            (string_to_array(string_agg(top_area_safs.application_count::TEXT,','),','))[5] AS top_saf_fifth_ward_count
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
                                                WHERE application_date BETWEEN '2023-04-01' AND '2024-03-31'
                                            
                                                GROUP BY ward_mstr_id, ulb_id

                                                UNION ALL 

                                                SELECT 
                                                    COUNT(id) AS application_count,
                                                    ward_mstr_id,
                                                    ulb_id
                                                FROM prop_safs
                                                WHERE application_date BETWEEN '2023-04-01' AND '2024-03-31'
                                                GROUP BY ward_mstr_id, ulb_id

                                                UNION ALL 

                                                SELECT 
                                                    COUNT(id) AS application_count,
                                                    ward_mstr_id,
                                                    ulb_id
                                                FROM prop_rejected_safs
                                                WHERE application_date BETWEEN '2023-04-01' AND '2024-03-31'
                                            
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
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[1] AS defaulter_first_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[2] AS defaulter_sec_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[3] AS defaulter_third_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[4] AS defaulter_forth_ward_no,
                            (string_to_array(string_agg(a.ward_name::TEXT,','),','))[5] AS defaulter_fifth_ward_no,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[1] AS defaulter_first_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[2] AS defaulter_sec_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[3] AS defaulter_third_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[4] AS defaulter_forth_ward_prop_cnt,
                            (string_to_array(string_agg(a.defaulter_property_cnt::TEXT,','),','))[5] AS defaulter_fifth_ward_prop_cnt,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[1] AS defaulter_first_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[2] AS defaulter_sec_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[3] AS defaulter_third_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[4] AS defaulter_forth_unpaid_amount,
                            (string_to_array(string_agg(a.unpaid_amount::TEXT,','),','))[5] AS defaulter_fifth_unpaid_amount
      
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
                                                WHERE fyear='2023-2024'								
                                                AND status=1 
                                                GROUP BY property_id
                                                ORDER BY property_id
                                        ) a 
                                        JOIN prop_properties p ON p.id=a.property_id
                                        JOIN ulb_ward_masters w ON w.id=p.ward_mstr_id
                                          
                                        WHERE a.demand_cnt=a.unpaid_count 
                                        AND p.status=1
                                          
                                        GROUP BY w.ward_name ,p.ulb_id
                                          
                                        ORDER BY defaulter_property_cnt DESC 
         
                                ) a
                                group by ulb_id
                        ),
                        
                         


                        total_props.*,
                            total_assessment.*,
                            total_occupancy_props.*,
                            current_payments.*,
                            total_vacant_land.*,
                            null_prop_data.*,
                            null_floor_data.*,
                            demand.*,
                            property_use_type.*,
                            zone_dtd_collection.*,
                            top_wards_collections.*,
                            top_area_safs.*,
                            area_wise_defaulter.*,




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